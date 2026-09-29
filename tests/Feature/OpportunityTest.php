<?php

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Support\OpportunityStage;
use Inertia\Testing\AssertableInertia as Assert;

function opportunityUser(string $slug): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => $slug],
    );

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

test('creating an opportunity writes opening stage history and sets probability', function () {
    $rep = opportunityUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);

    $response = $this->actingAs($rep)->post(route('opportunities.store'), [
        'name' => 'Northwind Expansion',
        'account_id' => $account->id,
        'amount' => '25000.00',
        'close_date' => now()->addMonth()->toDateString(),
        'stage' => OpportunityStage::QUALIFICATION,
        'type' => 'New Business',
    ]);

    $opportunity = Opportunity::query()->where('name', 'Northwind Expansion')->first();

    expect($opportunity)->not->toBeNull()
        ->and($opportunity?->probability)->toBe(10)
        ->and($opportunity?->is_closed)->toBeFalse()
        ->and($opportunity?->is_won)->toBeFalse()
        ->and($opportunity?->owner_id)->toBe($rep->id);

    $response->assertRedirect(route('opportunities.show', $opportunity));

    $history = OpportunityStageHistory::query()
        ->where('opportunity_id', $opportunity->id)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history?->from_stage)->toBeNull()
        ->and($history?->to_stage)->toBe(OpportunityStage::QUALIFICATION)
        ->and($history?->probability)->toBe(10)
        ->and($history?->user_id)->toBe($rep->id);
});

test('updating stage refreshes probability and appends stage history', function () {
    $rep = opportunityUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);
    $opportunity = Opportunity::factory()->create([
        'owner_id' => $rep->id,
        'account_id' => $account->id,
        'stage' => OpportunityStage::QUALIFICATION,
        'amount' => 10000,
        'close_date' => now()->addMonth()->toDateString(),
    ]);

    OpportunityStageHistory::query()->create([
        'opportunity_id' => $opportunity->id,
        'from_stage' => null,
        'to_stage' => OpportunityStage::QUALIFICATION,
        'probability' => 10,
        'user_id' => $rep->id,
    ]);

    $this->actingAs($rep)
        ->put(route('opportunities.update', $opportunity), [
            'name' => $opportunity->name,
            'account_id' => $account->id,
            'amount' => '10000.00',
            'close_date' => now()->addMonth()->toDateString(),
            'stage' => OpportunityStage::CLOSED_WON,
        ])
        ->assertRedirect(route('opportunities.show', $opportunity));

    $opportunity->refresh();

    expect($opportunity->stage)->toBe(OpportunityStage::CLOSED_WON)
        ->and($opportunity->probability)->toBe(100)
        ->and($opportunity->is_closed)->toBeTrue()
        ->and($opportunity->is_won)->toBeTrue()
        ->and($opportunity->expected_revenue)->toBe('10000.00');

    $history = OpportunityStageHistory::query()
        ->where('opportunity_id', $opportunity->id)
        ->where('to_stage', OpportunityStage::CLOSED_WON)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history?->from_stage)->toBe(OpportunityStage::QUALIFICATION)
        ->and($history?->probability)->toBe(100);
});

test('past close dates are rejected on create and edit', function () {
    $rep = opportunityUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);
    $opportunity = Opportunity::factory()->create([
        'owner_id' => $rep->id,
        'account_id' => $account->id,
        'close_date' => now()->addMonth()->toDateString(),
    ]);

    $this->actingAs($rep)
        ->from(route('opportunities.create'))
        ->post(route('opportunities.store'), [
            'name' => 'Late Deal',
            'account_id' => $account->id,
            'close_date' => now()->subDay()->toDateString(),
            'stage' => OpportunityStage::QUALIFICATION,
        ])
        ->assertRedirect(route('opportunities.create'))
        ->assertSessionHasErrors('close_date');

    $this->actingAs($rep)
        ->from(route('opportunities.edit', $opportunity))
        ->put(route('opportunities.update', $opportunity), [
            'name' => $opportunity->name,
            'account_id' => $account->id,
            'close_date' => now()->subDay()->toDateString(),
            'stage' => OpportunityStage::QUALIFICATION,
        ])
        ->assertRedirect(route('opportunities.edit', $opportunity))
        ->assertSessionHasErrors('close_date');
});

test('archive sets archived_at and hides the row from the default list', function () {
    $rep = opportunityUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);
    $opportunity = Opportunity::factory()->create([
        'owner_id' => $rep->id,
        'account_id' => $account->id,
        'name' => 'Archive Me',
    ]);

    $this->actingAs($rep)
        ->delete(route('opportunities.destroy', $opportunity))
        ->assertRedirect(route('opportunities.index'));

    expect($opportunity->fresh()->archived_at)->not->toBeNull()
        ->and(Opportunity::query()->whereKey($opportunity->id)->exists())->toBeTrue();

    $this->actingAs($rep)
        ->get(route('opportunities.index', ['view' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Opportunities/Index')
            ->where('opportunities.data', fn ($rows) => collect($rows)->pluck('id')->doesntContain($opportunity->id))
        );

    $this->actingAs($rep)
        ->get(route('opportunities.index', ['view' => 'all', 'archived' => 1]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Opportunities/Index')
            ->where('filters.archived', true)
            ->where('opportunities.data.0.id', $opportunity->id)
        );
});

test('clone can include related tasks events and notes', function () {
    $rep = opportunityUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);
    $source = Opportunity::factory()->create([
        'owner_id' => $rep->id,
        'account_id' => $account->id,
        'name' => 'Source Deal',
        'stage' => OpportunityStage::MEETING_SCHEDULED,
        'close_date' => now()->addWeeks(2)->toDateString(),
    ]);

    Task::factory()->create([
        'owner_id' => $rep->id,
        'related_type' => Opportunity::class,
        'related_id' => $source->id,
        'subject' => 'Call prospect',
    ]);

    Event::factory()->create([
        'owner_id' => $rep->id,
        'related_type' => Opportunity::class,
        'related_id' => $source->id,
        'subject' => 'Discovery call',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
    ]);

    Note::factory()->create([
        'owner_id' => $rep->id,
        'notable_type' => Opportunity::class,
        'notable_id' => $source->id,
        'body' => 'Pricing notes',
    ]);

    $this->actingAs($rep)
        ->post(route('opportunities.clone', $source), [
            'include_related' => true,
        ])
        ->assertRedirect();

    $copy = Opportunity::query()->where('name', 'Copy of Source Deal')->first();

    expect($copy)->not->toBeNull()
        ->and($copy?->stage)->toBe(OpportunityStage::MEETING_SCHEDULED)
        ->and($copy?->probability)->toBe(20)
        ->and($copy?->owner_id)->toBe($rep->id);

    expect(OpportunityStageHistory::query()->where('opportunity_id', $copy->id)->count())->toBe(1)
        ->and(Task::query()->where('related_id', $copy->id)->where('related_type', Opportunity::class)->count())->toBe(1)
        ->and(Event::query()->where('related_id', $copy->id)->where('related_type', Opportunity::class)->count())->toBe(1)
        ->and(Note::query()->where('notable_id', $copy->id)->where('notable_type', Opportunity::class)->count())->toBe(1);
});

test('change owner writes activity log and can transfer open activities', function () {
    $manager = opportunityUser('sales-manager');
    $rep = opportunityUser('sales-rep');
    $other = opportunityUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);

    $opportunity = Opportunity::factory()->create([
        'owner_id' => $rep->id,
        'account_id' => $account->id,
        'name' => 'Owner Deal',
    ]);

    $task = Task::factory()->create([
        'owner_id' => $rep->id,
        'assigned_to_id' => $rep->id,
        'related_type' => Opportunity::class,
        'related_id' => $opportunity->id,
        'status' => 'In Progress',
    ]);

    $event = Event::factory()->create([
        'owner_id' => $rep->id,
        'assigned_to_id' => $rep->id,
        'related_type' => Opportunity::class,
        'related_id' => $opportunity->id,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
    ]);

    $this->actingAs($manager)
        ->post(route('opportunities.owner', $opportunity), [
            'owner_id' => $other->id,
            'transfer_activities' => true,
        ])
        ->assertRedirect(route('opportunities.show', $opportunity));

    expect($opportunity->fresh()->owner_id)->toBe($other->id)
        ->and($task->fresh()->owner_id)->toBe($other->id)
        ->and($task->fresh()->assigned_to_id)->toBe($other->id)
        ->and($event->fresh()->owner_id)->toBe($other->id);

    $log = ActivityLog::query()
        ->where('subject_type', Opportunity::class)
        ->where('subject_id', $opportunity->id)
        ->where('action', 'owner_changed')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($manager->id)
        ->and($log->properties['previous_owner_id'])->toBe($rep->id)
        ->and($log->properties['new_owner_id'])->toBe($other->id);
});

test('detail page exposes stage path and expected revenue', function () {
    $rep = opportunityUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);
    $opportunity = Opportunity::factory()->create([
        'owner_id' => $rep->id,
        'account_id' => $account->id,
        'stage' => OpportunityStage::PROPOSAL,
        'amount' => 20000,
        'name' => 'Detail Deal',
    ]);

    $this->actingAs($rep)
        ->get(route('opportunities.show', $opportunity))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Opportunities/Show')
            ->where('opportunity.name', 'Detail Deal')
            ->where('opportunity.probability', 65)
            ->where('opportunity.expected_revenue', '13000.00')
            ->where('stages', array_keys(OpportunityStage::PROBABILITIES))
            ->where('can.update', true)
            ->where('can.clone', true)
        );
});
