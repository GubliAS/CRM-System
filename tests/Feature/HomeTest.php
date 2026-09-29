<?php

use App\Models\Account;
use App\Models\AssistantRecommendationDismissal;
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
        ->and($recommendation['recommendable_type'])->toBe(Account::class);

    // Post the exact payload type the Home page emits (what Vue posts on dismiss).
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
            ->where('recommendable_type', Account::class)
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
