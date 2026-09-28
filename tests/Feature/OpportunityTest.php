<?php

use App\Models\Account;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Task;
use App\Support\OpportunityStage;
use Inertia\Testing\AssertableInertia as Assert;

test('creating at qualification stores probability flags and one history row', function () {
    $rep = crmUser('sales-rep');
    $other = crmUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);

    $response = $this->actingAs($rep)->post(route('opportunities.store'), [
        'name' => 'Northwind expansion',
        'account_id' => $account->id,
        'close_date' => now()->addWeek()->toDateString(),
        'stage' => OpportunityStage::QUALIFICATION,
        'amount' => 2500,
        'owner_id' => $other->id,
    ]);

    $opportunity = Opportunity::query()->where('name', 'Northwind expansion')->first();

    expect($opportunity)->not->toBeNull()
        ->and($opportunity->probability)->toBe(10)
        ->and($opportunity->is_closed)->toBeFalse()
        ->and($opportunity->is_won)->toBeFalse()
        ->and($opportunity->owner_id)->toBe($rep->id)
        ->and($opportunity->stageHistories)->toHaveCount(1)
        ->and($opportunity->stageHistories->first()->from_stage)->toBeNull()
        ->and($opportunity->stageHistories->first()->to_stage)->toBe(OpportunityStage::QUALIFICATION)
        ->and($opportunity->stageHistories->first()->probability)->toBe(10)
        ->and($opportunity->stageHistories->first()->user_id)->toBe($rep->id);

    $response->assertRedirect(route('opportunities.show', $opportunity));
});

test('changing the stage updates probability flags expected revenue and history', function () {
    $rep = crmUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);
    $opportunity = Opportunity::factory()->create([
        'owner_id' => $rep->id,
        'account_id' => $account->id,
        'stage' => OpportunityStage::QUALIFICATION,
        'amount' => 2000,
        'close_date' => now()->addMonth()->toDateString(),
    ]);

    $fields = [
        'name' => $opportunity->name,
        'account_id' => $account->id,
        'amount' => 2000,
        'close_date' => $opportunity->close_date->toDateString(),
        'type' => $opportunity->type,
        'lead_source' => $opportunity->lead_source,
    ];

    $this->actingAs($rep)
        ->put(route('opportunities.update', $opportunity), [
            ...$fields,
            'stage' => OpportunityStage::QUALIFICATION,
            'description' => 'Notes only',
        ])
        ->assertRedirect(route('opportunities.show', $opportunity));

    $opportunity->refresh();

    expect($opportunity->description)->toBe('Notes only')
        ->and($opportunity->probability)->toBe(10)
        ->and($opportunity->stageHistories)->toHaveCount(0);

    $this->actingAs($rep)
        ->put(route('opportunities.update', $opportunity), [
            ...$fields,
            'stage' => OpportunityStage::CLOSED_WON,
            'description' => 'Notes only',
        ])
        ->assertRedirect(route('opportunities.show', $opportunity));

    $opportunity->refresh();

    expect($opportunity->probability)->toBe(100)
        ->and($opportunity->is_closed)->toBeTrue()
        ->and($opportunity->is_won)->toBeTrue()
        ->and($opportunity->expected_revenue)->toBe($opportunity->amount)
        ->and($opportunity->stageHistories)->toHaveCount(1)
        ->and($opportunity->stageHistories->first()->from_stage)->toBe(OpportunityStage::QUALIFICATION)
        ->and($opportunity->stageHistories->first()->to_stage)->toBe(OpportunityStage::CLOSED_WON)
        ->and($opportunity->stageHistories->first()->probability)->toBe(100);

    $this->actingAs($rep)
        ->put(route('opportunities.update', $opportunity), [
            ...$fields,
            'stage' => OpportunityStage::CLOSED_LOST,
            'description' => 'Notes only',
        ])
        ->assertRedirect(route('opportunities.show', $opportunity));

    $opportunity->refresh();

    expect($opportunity->probability)->toBe(0)
        ->and($opportunity->is_closed)->toBeTrue()
        ->and($opportunity->is_won)->toBeFalse()
        ->and($opportunity->expected_revenue)->toBe('0.00')
        ->and($opportunity->stageHistories)->toHaveCount(2);
});

test('a past close date fails validation', function () {
    $rep = crmUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);

    $this->actingAs($rep)
        ->from(route('opportunities.create'))
        ->post(route('opportunities.store'), [
            'name' => 'Late deal',
            'account_id' => $account->id,
            'close_date' => now()->subDay()->toDateString(),
            'stage' => OpportunityStage::QUALIFICATION,
        ])
        ->assertRedirect(route('opportunities.create'))
        ->assertSessionHasErrors('close_date');

    expect(Opportunity::query()->where('name', 'Late deal')->exists())->toBeFalse();

    $this->actingAs($rep)
        ->from(route('opportunities.create'))
        ->post(route('opportunities.store'), [
            'name' => 'Zero amount',
            'account_id' => $account->id,
            'close_date' => now()->addDay()->toDateString(),
            'stage' => OpportunityStage::QUALIFICATION,
            'amount' => 0,
        ])
        ->assertSessionHasErrors('amount');

    $this->actingAs($rep)
        ->from(route('opportunities.create'))
        ->post(route('opportunities.store'), [
            'name' => 'Wrong probability',
            'account_id' => $account->id,
            'close_date' => now()->addDay()->toDateString(),
            'stage' => OpportunityStage::QUALIFICATION,
            'probability' => 99,
        ])
        ->assertSessionHasErrors('probability');
});

test('archive sets archived_at and the default list hides the row', function () {
    $rep = crmUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);
    $visible = Opportunity::factory()->create([
        'name' => 'Open deal',
        'owner_id' => $rep->id,
        'account_id' => $account->id,
    ]);
    $hidden = Opportunity::factory()->create([
        'name' => 'Old deal',
        'owner_id' => $rep->id,
        'account_id' => $account->id,
    ]);

    $this->actingAs($rep)
        ->delete(route('opportunities.destroy', $hidden))
        ->assertRedirect(route('opportunities.show', $hidden));

    $hidden->refresh();

    expect($hidden->archived_at)->not->toBeNull()
        ->and(Opportunity::query()->whereKey($hidden->id)->exists())->toBeTrue();

    $this->actingAs($rep)
        ->get(route('opportunities.show', $hidden))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Opportunities/Show')
            ->where('opportunity.archived_at', fn ($value) => $value !== null)
        );

    $this->actingAs($rep)
        ->get(route('opportunities.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Opportunities/Index')
            ->where('opportunities.total', 1)
            ->where('opportunities.data.0.id', $visible->id)
        );

    $this->actingAs($rep)
        ->get(route('opportunities.index', ['show_archived' => 1]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('opportunities.total', 2)
            ->where('opportunities.data', fn ($rows) => $rows->pluck('id')->contains($hidden->id))
        );
});

test('another sales rep receives 403 and a manager can view', function () {
    $rep = crmUser('sales-rep');
    $other = crmUser('sales-rep');
    $manager = crmUser('sales-manager');
    $account = Account::factory()->create(['owner_id' => $rep->id]);
    $opportunity = Opportunity::factory()->create([
        'owner_id' => $rep->id,
        'account_id' => $account->id,
    ]);

    $this->actingAs($other)
        ->get(route('opportunities.show', $opportunity))
        ->assertForbidden();

    $this->actingAs($other)
        ->patch(route('opportunities.owner', $opportunity), [
            'owner_id' => $other->id,
        ])
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('opportunities.show', $opportunity))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Opportunities/Show')
            ->where('opportunity.id', $opportunity->id)
            ->has('opportunity.stage_histories')
            ->has('stagePath', 5)
            ->where('stagePath.0.name', OpportunityStage::QUALIFICATION)
            ->where('stagePath.0.state', 'current')
            ->where('stagePath.4.name', OpportunityStage::CLOSED_WON)
        );

    expect($opportunity->fresh()->owner_id)->toBe($rep->id);
});

test('a sales rep can reassign an opportunity they own', function () {
    $rep = crmUser('sales-rep');
    $manager = crmUser('sales-manager');
    $account = Account::factory()->create(['owner_id' => $rep->id]);
    $opportunity = Opportunity::factory()->create([
        'owner_id' => $rep->id,
        'account_id' => $account->id,
    ]);

    $this->actingAs($rep)
        ->patch(route('opportunities.owner', $opportunity), [
            'owner_id' => $manager->id,
        ])
        ->assertRedirect(route('opportunities.show', $opportunity));

    expect($opportunity->fresh()->owner_id)->toBe($manager->id)
        ->and($opportunity->fresh()->stageHistories)->toHaveCount(0);
});

test('clone copies the opportunity without stage history and can include related records', function () {
    $rep = crmUser('sales-rep');
    $account = Account::factory()->create(['owner_id' => $rep->id]);

    $this->actingAs($rep)->post(route('opportunities.store'), [
        'name' => str_repeat('A', 120),
        'account_id' => $account->id,
        'close_date' => now()->addWeek()->toDateString(),
        'stage' => OpportunityStage::MEETING_SCHEDULED,
        'amount' => 4000,
    ])->assertRedirect();

    $source = Opportunity::query()->where('name', str_repeat('A', 120))->first();

    Note::factory()->create([
        'notable_type' => Opportunity::class,
        'notable_id' => $source->id,
        'owner_id' => $rep->id,
        'body' => 'Keep this note.',
    ]);
    Task::factory()->create([
        'related_type' => Opportunity::class,
        'related_id' => $source->id,
        'owner_id' => $rep->id,
        'subject' => 'Keep this task',
    ]);
    Event::factory()->create([
        'related_type' => Opportunity::class,
        'related_id' => $source->id,
        'owner_id' => $rep->id,
        'subject' => 'Keep this event',
    ]);
    Lead::factory()->create([
        'owner_id' => $rep->id,
        'converted' => true,
        'converted_account_id' => $account->id,
        'converted_opportunity_id' => $source->id,
        'lead_status' => 'Converted',
    ]);

    $this->actingAs($rep)
        ->post(route('opportunities.clone', $source), [
            'include_related' => true,
        ])
        ->assertRedirect();

    $clone = Opportunity::query()->where('owner_id', $rep->id)->whereKeyNot($source->id)->first();

    expect($clone)->not->toBeNull()
        ->and(mb_strlen($clone->name))->toBe(120)
        ->and($clone->name)->toStartWith('Copy of ')
        ->and($clone->archived_at)->toBeNull()
        ->and($clone->owner_id)->toBe($rep->id)
        ->and($clone->stage)->toBe(OpportunityStage::MEETING_SCHEDULED)
        ->and($clone->probability)->toBe(20)
        ->and($clone->stageHistories)->toHaveCount(1)
        ->and($clone->stageHistories->first()->from_stage)->toBeNull()
        ->and($source->fresh()->stageHistories)->toHaveCount(1)
        ->and($clone->notes)->toHaveCount(1)
        ->and($clone->notes->first()->body)->toBe('Keep this note.')
        ->and($clone->relatedTasks)->toHaveCount(1)
        ->and($clone->relatedEvents)->toHaveCount(1)
        ->and($clone->convertedLeads)->toHaveCount(0)
        ->and($source->fresh()->convertedLeads)->toHaveCount(1);

    $past = Opportunity::factory()->create([
        'owner_id' => $rep->id,
        'account_id' => $account->id,
        'close_date' => now()->subDays(3)->toDateString(),
    ]);

    $this->actingAs($rep)
        ->from(route('opportunities.show', $past))
        ->post(route('opportunities.clone', $past), [
            'include_related' => false,
        ])
        ->assertRedirect(route('opportunities.show', $past))
        ->assertSessionHasErrors('close_date');

    $this->actingAs($rep)
        ->post(route('opportunities.clone', $past), [
            'include_related' => false,
            'close_date' => now()->toDateString(),
        ])
        ->assertRedirect();

    $datedClone = Opportunity::query()
        ->where('name', 'Copy of '.$past->name)
        ->first();

    expect($datedClone)->not->toBeNull()
        ->and($datedClone->close_date->toDateString())->toBe(now()->toDateString())
        ->and($datedClone->notes)->toHaveCount(0)
        ->and($datedClone->stageHistories)->toHaveCount(1);
});
