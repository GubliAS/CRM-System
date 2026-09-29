<?php

use App\Models\Account;
use App\Models\AssistantRecommendationDismissal;
use App\Models\Event;
use App\Models\Opportunity;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Support\OpportunityStage;
use Inertia\Testing\AssertableInertia as Assert;

function homeUser(string $slug = 'sales-rep'): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => $slug],
    );

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

test('home shows an empty message when no tasks are due today', function () {
    $user = homeUser();

    Task::factory()->create([
        'subject' => 'Not due today',
        'due_on' => now()->addDay()->toDateString(),
        'status' => 'Not Started',
        'assigned_to_id' => $user->id,
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('tasksDueToday', 0)
            ->where('eventsToday', [])
            ->has('pipeline', 6)
            ->has('recommendations')
        );
});

test('a dismissed recommendation stays dismissed for that user', function () {
    $user = homeUser();

    $account = Account::factory()->create([
        'name' => 'Silent Prospect Co',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $account->forceFill([
        'updated_at' => now()->subDays(45),
        'created_at' => now()->subDays(60),
    ])->saveQuietly();

    $home = $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('recommendations', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['rule'] === AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT
                    && (int) $row['recommendable_id'] === $account->id
                    && $row['recommendable_type'] === AssistantRecommendationDismissal::TYPE_ACCOUNT
            ))
        );

    $recommendation = collect($home->inertiaProps('recommendations'))->first(
        fn ($row) => $row['rule'] === AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT
            && (int) $row['recommendable_id'] === $account->id
    );

    expect($recommendation)->not->toBeNull()
        ->and($recommendation['recommendable_type'])->toBe('account');

    // Post the exact payload type the Home page emits (short morph alias).
    $this->actingAs($user)
        ->post(route('home.recommendations.dismiss'), [
            'rule' => $recommendation['rule'],
            'recommendable_type' => $recommendation['recommendable_type'],
            'recommendable_id' => $recommendation['recommendable_id'],
        ])
        ->assertRedirect(route('home'));

    expect(
        AssistantRecommendationDismissal::query()
            ->where('user_id', $user->id)
            ->where('rule', AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT)
            ->where('recommendable_type', 'account')
            ->where('recommendable_id', $account->id)
            ->exists()
    )->toBeTrue();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('recommendations', fn ($rows) => collect($rows)->doesntContain(
                fn ($row) => $row['rule'] === AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT
                    && (int) $row['recommendable_id'] === $account->id
            ))
        );
});

test('dismiss accepts legacy FQCN recommendable_type from older clients', function () {
    $user = homeUser();

    $account = Account::factory()->create([
        'name' => 'FQCN Client Co',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $account->forceFill([
        'updated_at' => now()->subDays(45),
        'created_at' => now()->subDays(60),
    ])->saveQuietly();

    $this->actingAs($user)
        ->post(route('home.recommendations.dismiss'), [
            'rule' => AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT,
            'recommendable_type' => Account::class,
            'recommendable_id' => $account->id,
        ])
        ->assertRedirect(route('home'));

    expect(
        AssistantRecommendationDismissal::query()
            ->where('user_id', $user->id)
            ->where('recommendable_type', 'account')
            ->where('recommendable_id', $account->id)
            ->exists()
    )->toBeTrue();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('recommendations', fn ($rows) => collect($rows)->doesntContain(
                fn ($row) => (int) $row['recommendable_id'] === $account->id
                    && $row['rule'] === AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT
            ))
        );
});

test('legacy FQCN dismissal rows still hide recommendations', function () {
    $user = homeUser();

    $account = Account::factory()->create([
        'name' => 'Legacy FQCN Dismissed Co',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $account->forceFill([
        'updated_at' => now()->subDays(45),
        'created_at' => now()->subDays(60),
    ])->saveQuietly();

    AssistantRecommendationDismissal::query()->create([
        'user_id' => $user->id,
        'rule' => AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT,
        'recommendable_type' => Account::class,
        'recommendable_id' => $account->id,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('recommendations', fn ($rows) => collect($rows)->doesntContain(
                fn ($row) => $row['rule'] === AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT
                    && (int) $row['recommendable_id'] === $account->id
            ))
        );
});

test('legacy short-slug dismissal rows still hide recommendations', function () {
    $user = homeUser();

    $account = Account::factory()->create([
        'name' => 'Legacy Dismissed Co',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $account->forceFill([
        'updated_at' => now()->subDays(45),
        'created_at' => now()->subDays(60),
    ])->saveQuietly();

    AssistantRecommendationDismissal::query()->create([
        'user_id' => $user->id,
        'rule' => AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT,
        'recommendable_type' => 'account',
        'recommendable_id' => $account->id,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('recommendations', fn ($rows) => collect($rows)->doesntContain(
                fn ($row) => $row['rule'] === AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT
                    && (int) $row['recommendable_id'] === $account->id
            ))
        );
});

test('dismissed ids are excluded before the assistant limit so later accounts still appear', function () {
    $user = homeUser();

    $accounts = collect(range(1, 12))->map(function (int $i) use ($user) {
        $account = Account::factory()->create([
            'name' => sprintf('Inactive %02d', $i),
            'owner_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $account->forceFill([
            'updated_at' => now()->subDays(40 + $i),
            'created_at' => now()->subDays(60),
        ])->saveQuietly();

        return $account;
    });

    // Dismiss the 10 oldest (would monopolize a limit-then-filter query).
    foreach ($accounts->sortBy('updated_at')->take(10) as $account) {
        AssistantRecommendationDismissal::query()->create([
            'user_id' => $user->id,
            'rule' => AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT,
            'recommendable_type' => 'account',
            'recommendable_id' => $account->id,
        ]);
    }

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('recommendations', function ($rows) use ($accounts) {
                $ids = collect($rows)
                    ->where('rule', AssistantRecommendationDismissal::RULE_INACTIVE_ACCOUNT)
                    ->pluck('recommendable_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $remaining = $accounts->sortBy('updated_at')->slice(10)->pluck('id')->map(fn ($id) => (int) $id)->all();

                return count(array_intersect($ids, $remaining)) === count($remaining)
                    && count(array_intersect($ids, $accounts->sortBy('updated_at')->take(10)->pluck('id')->all())) === 0;
            })
        );
});

test('completing a task from home removes it from tasks due today', function () {
    $user = homeUser();

    $task = Task::factory()->create([
        'subject' => 'Call the prospect today',
        'due_on' => now()->toDateString(),
        'status' => 'Not Started',
        'assigned_to_id' => $user->id,
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasksDueToday', 1)
            ->where('tasksDueToday.0.id', $task->id)
        );

    $this->actingAs($user)
        ->post(route('tasks.complete', $task))
        ->assertRedirect();

    expect($task->fresh()->status)->toBe('Completed');

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('tasksDueToday', 0));
});

test('pipeline stage links filter the opportunity list', function () {
    $user = homeUser();

    Opportunity::factory()->create([
        'name' => 'Qualification deal',
        'stage' => OpportunityStage::QUALIFICATION,
        'close_date' => now()->toDateString(),
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Opportunity::factory()->create([
        'name' => 'Negotiation deal',
        'stage' => OpportunityStage::NEGOTIATION,
        'close_date' => now()->toDateString(),
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('opportunities.index', [
            'stage' => OpportunityStage::QUALIFICATION,
            'year' => now()->year,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Opportunities/Index')
            ->where('filters.stage', OpportunityStage::QUALIFICATION)
            ->where('opportunities.data', fn ($rows) => collect($rows)->pluck('name')->all() === ['Qualification deal'])
        );
});

test('home pipeline includes every stage with count value percent and stage filter links', function () {
    $user = homeUser();

    Opportunity::factory()->create([
        'name' => 'Qualify now',
        'stage' => OpportunityStage::QUALIFICATION,
        'amount' => 1000,
        'close_date' => now()->toDateString(),
        'lead_source' => 'Web',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Opportunity::factory()->create([
        'name' => 'Negotiate now',
        'stage' => OpportunityStage::NEGOTIATION,
        'amount' => 3000,
        'close_date' => now()->toDateString(),
        'lead_source' => 'Web',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Opportunity::factory()->create([
        'name' => 'Last year deal',
        'stage' => OpportunityStage::QUALIFICATION,
        'amount' => 99999,
        'close_date' => now()->subYear()->toDateString(),
        'lead_source' => 'Trade Show',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('year', now()->year)
            ->where('pipelineTotal', 4000)
            ->has('pipeline', 6)
            ->where('pipeline', function ($rows) {
                $stages = collect($rows)->pluck('stage')->all();
                $expected = array_keys(OpportunityStage::PROBABILITIES);

                if ($stages !== $expected) {
                    return false;
                }

                $byStage = collect($rows)->keyBy('stage');
                $qualify = $byStage->get(OpportunityStage::QUALIFICATION);
                $negotiate = $byStage->get(OpportunityStage::NEGOTIATION);

                return (int) $qualify['count'] === 1
                    && (float) $qualify['value'] === 1000.0
                    && (float) $qualify['percent'] === 25.0
                    && str_contains((string) $qualify['href'], 'stage='.urlencode(OpportunityStage::QUALIFICATION))
                    && str_contains((string) $qualify['href'], 'year='.now()->year)
                    && (int) $negotiate['count'] === 1
                    && (float) $negotiate['value'] === 3000.0
                    && (float) $negotiate['percent'] === 75.0;
            })
            ->where('revenueBySourceTotal', 4000)
            ->where('revenueBySource', function ($rows) {
                $web = collect($rows)->firstWhere('source', 'Web');

                return $web !== null
                    && (int) $web['count'] === 2
                    && (float) $web['value'] === 4000.0
                    && (float) $web['percent'] === 100.0
                    && collect($rows)->doesntContain(fn ($row) => $row['source'] === 'Trade Show');
            })
        );
});

test('home shows todays events and an empty list when none are scheduled', function () {
    $user = homeUser();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('eventsToday', [])
        );

    $starts = now()->setTime(14, 0);

    $event = Event::factory()->create([
        'subject' => 'Afternoon check-in',
        'starts_at' => $starts,
        'ends_at' => $starts->copy()->addHour(),
        'all_day' => false,
        'owner_id' => $user->id,
        'assigned_to_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('eventsToday', 1)
            ->where('eventsToday.0.id', $event->id)
            ->where('eventsToday.0.subject', 'Afternoon check-in')
            ->where('eventsToday.0.url', route('events.show', $event))
        );
});

test('home lists key open opportunities with linked records', function () {
    $user = homeUser();

    $account = Account::factory()->create([
        'name' => 'Key Deal Account',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $opportunity = Opportunity::factory()->create([
        'name' => 'Largest open deal',
        'account_id' => $account->id,
        'amount' => 50000,
        'close_date' => now()->addDays(20)->toDateString(),
        'stage' => OpportunityStage::PROPOSAL,
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Opportunity::factory()->create([
        'name' => 'Already closed',
        'account_id' => $account->id,
        'amount' => 999999,
        'close_date' => now()->toDateString(),
        'stage' => OpportunityStage::CLOSED_WON,
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('keyOpportunities', 1)
            ->where('keyOpportunities.0.id', $opportunity->id)
            ->where('keyOpportunities.0.name', 'Largest open deal')
            ->where('keyOpportunities.0.account.name', 'Key Deal Account')
            ->where('keyOpportunities.0.amount', '50000.00')
            ->where('keyOpportunities.0.stage', OpportunityStage::PROPOSAL)
            ->where('keyOpportunities.0.url', route('opportunities.show', $opportunity))
        );
});

test('home recommends opportunities near close with no recent update', function () {
    $user = homeUser();

    $opportunity = Opportunity::factory()->create([
        'name' => 'Stale near close',
        'stage' => OpportunityStage::NEGOTIATION,
        'amount' => 12000,
        'close_date' => now()->addDays(5)->toDateString(),
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $opportunity->forceFill([
        'updated_at' => now()->subDays(10),
        'created_at' => now()->subDays(40),
    ])->saveQuietly();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('recommendations', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['rule'] === AssistantRecommendationDismissal::RULE_STALE_OPPORTUNITY
                    && (int) $row['recommendable_id'] === $opportunity->id
                    && $row['recommendable_type'] === AssistantRecommendationDismissal::TYPE_OPPORTUNITY
                    && $row['url'] === route('opportunities.show', $opportunity)
            ))
        );
});
