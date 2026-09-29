<?php

use App\Actions\Leads\ConvertLead;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function leadUser(string $slug): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => $slug],
    );

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

test('conversion creates account contact and optional opportunity', function () {
    $rep = leadUser('sales-rep');
    $lead = Lead::factory()->create([
        'owner_id' => $rep->id,
        'company' => 'Contoso Logistics',
        'last_name' => 'Demo',
        'first_name' => 'Alex',
        'email' => 'alex@example.com',
    ]);

    Task::factory()->create([
        'owner_id' => $rep->id,
        'assigned_to_id' => $rep->id,
        'related_type' => Lead::class,
        'related_id' => $lead->id,
        'status' => 'Not Started',
        'subject' => 'Open follow-up',
    ]);

    $this->actingAs($rep)
        ->post(route('leads.convert', $lead), [
            'account_mode' => 'new',
            'create_opportunity' => true,
            'opportunity_name' => 'Contoso Deal',
            'opportunity_amount' => '15000.00',
            'opportunity_close_date' => now()->addMonth()->toDateString(),
            'opportunity_stage' => 'Qualification',
            'transfer_activities' => true,
        ])
        ->assertRedirect(route('leads.show', $lead));

    $lead->refresh();

    expect($lead->converted)->toBeTrue()
        ->and($lead->lead_status)->toBe('Converted')
        ->and($lead->converted_account_id)->not->toBeNull()
        ->and($lead->converted_contact_id)->not->toBeNull()
        ->and($lead->converted_opportunity_id)->not->toBeNull();

    $account = Account::query()->find($lead->converted_account_id);
    $contact = Contact::query()->find($lead->converted_contact_id);
    $opportunity = Opportunity::query()->find($lead->converted_opportunity_id);

    expect($account?->name)->toBe('Contoso Logistics')
        ->and($contact?->last_name)->toBe('Demo')
        ->and($contact?->account_id)->toBe($account->id)
        ->and($opportunity?->name)->toBe('Contoso Deal')
        ->and($opportunity?->account_id)->toBe($account->id)
        ->and($opportunity?->stage)->toBe('Qualification');

    $history = OpportunityStageHistory::query()
        ->where('opportunity_id', $opportunity->id)
        ->get();

    expect($history)->toHaveCount(1)
        ->and($history->first()->from_stage)->toBeNull()
        ->and($history->first()->to_stage)->toBe('Qualification')
        ->and($history->first()->probability)->toBe(10)
        ->and($history->first()->user_id)->toBe($rep->id);

    $task = Task::query()->where('subject', 'Open follow-up')->first();

    expect($task->related_type)->toBe(Opportunity::class)
        ->and($task->related_id)->toBe($opportunity->id)
        ->and($task->contact_id)->toBe($contact->id);
});

test('failed opportunity validation rolls back every conversion row', function () {
    $rep = leadUser('sales-rep');
    $lead = Lead::factory()->create([
        'owner_id' => $rep->id,
        'company' => 'Rollback Co',
        'last_name' => 'Sample',
    ]);

    $accountsBefore = Account::query()->count();
    $contactsBefore = Contact::query()->count();
    $opportunitiesBefore = Opportunity::query()->count();

    $this->actingAs($rep)
        ->from(route('leads.show', $lead))
        ->post(route('leads.convert', $lead), [
            'account_mode' => 'new',
            'create_opportunity' => true,
            'opportunity_name' => '',
            'opportunity_close_date' => now()->addMonth()->toDateString(),
            'opportunity_stage' => 'Qualification',
        ])
        ->assertRedirect(route('leads.show', $lead))
        ->assertSessionHasErrors('opportunity_name');

    expect(Account::query()->count())->toBe($accountsBefore)
        ->and(Contact::query()->count())->toBe($contactsBefore)
        ->and(Opportunity::query()->count())->toBe($opportunitiesBefore)
        ->and($lead->fresh()->converted)->toBeFalse();

    expect(fn () => app(ConvertLead::class)->handle($rep, $lead, [
        'account_mode' => 'new',
        'create_opportunity' => true,
        'opportunity_name' => '',
        'opportunity_close_date' => now()->addMonth()->toDateString(),
        'opportunity_stage' => 'Qualification',
    ]))->toThrow(ValidationException::class);

    expect(Account::query()->count())->toBe($accountsBefore)
        ->and(Contact::query()->count())->toBe($contactsBefore)
        ->and(Opportunity::query()->count())->toBe($opportunitiesBefore)
        ->and($lead->fresh()->converted)->toBeFalse()
        ->and(Account::query()->where('name', 'Rollback Co')->exists())->toBeFalse();
});

test('a converted lead cannot be edited', function () {
    $rep = leadUser('sales-rep');
    $manager = leadUser('sales-manager');
    $lead = Lead::factory()->create([
        'owner_id' => $rep->id,
        'company' => 'Locked Co',
        'last_name' => 'Locked',
        'converted' => true,
        'lead_status' => 'Converted',
    ]);

    $this->actingAs($rep)
        ->put(route('leads.update', $lead), [
            'last_name' => 'Changed',
            'company' => 'Locked Co',
            'lead_status' => 'Working',
        ])
        ->assertForbidden();

    $this->actingAs($manager)
        ->put(route('leads.update', $lead), [
            'last_name' => 'Changed',
            'company' => 'Locked Co',
            'lead_status' => 'Working',
        ])
        ->assertForbidden();

    $this->actingAs($rep)
        ->get(route('leads.edit', $lead))
        ->assertForbidden();

    $this->actingAs($rep)
        ->post(route('leads.convert', $lead), [
            'account_mode' => 'new',
        ])
        ->assertForbidden();

    expect($lead->fresh()->last_name)->toBe('Locked')
        ->and($lead->fresh()->lead_status)->toBe('Converted');
});

test('change owner writes activity log and can transfer open activities', function () {
    $manager = leadUser('sales-manager');
    $rep = leadUser('sales-rep');
    $other = leadUser('sales-rep');

    $lead = Lead::factory()->create([
        'owner_id' => $rep->id,
        'company' => 'Owner Co',
    ]);

    $task = Task::factory()->create([
        'owner_id' => $rep->id,
        'assigned_to_id' => $rep->id,
        'related_type' => Lead::class,
        'related_id' => $lead->id,
        'status' => 'In Progress',
    ]);

    $event = Event::factory()->create([
        'owner_id' => $rep->id,
        'assigned_to_id' => $rep->id,
        'related_type' => Lead::class,
        'related_id' => $lead->id,
        'ends_at' => now()->addDays(2),
    ]);

    $this->actingAs($manager)
        ->post(route('leads.owner', $lead), [
            'owner_id' => $other->id,
            'transfer_activities' => true,
        ])
        ->assertRedirect(route('leads.show', $lead));

    expect($lead->fresh()->owner_id)->toBe($other->id)
        ->and($task->fresh()->owner_id)->toBe($other->id)
        ->and($task->fresh()->assigned_to_id)->toBe($other->id)
        ->and($event->fresh()->owner_id)->toBe($other->id);

    $log = ActivityLog::query()
        ->where('subject_type', Lead::class)
        ->where('subject_id', $lead->id)
        ->where('action', 'owner_changed')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($manager->id)
        ->and($log->properties['previous_owner_id'])->toBe($rep->id)
        ->and($log->properties['new_owner_id'])->toBe($other->id);
});

test('a sales rep can create a lead and change status', function () {
    $rep = leadUser('sales-rep');

    $response = $this->actingAs($rep)->post(route('leads.store'), [
        'last_name' => 'Prospect',
        'company' => 'Acme Industries',
        'lead_status' => 'New',
        'email' => 'prospect@example.com',
    ]);

    $lead = Lead::query()->where('company', 'Acme Industries')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->owner_id)->toBe($rep->id)
        ->and($lead->lead_status)->toBe('New');

    $response->assertRedirect(route('leads.show', $lead));

    $this->actingAs($rep)
        ->post(route('leads.status', $lead), ['lead_status' => 'Working'])
        ->assertRedirect(route('leads.show', $lead));

    expect($lead->fresh()->lead_status)->toBe('Working');

    $this->actingAs($rep)
        ->get(route('leads.index', ['view' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Leads/Index')
            ->where('leads.data.0.id', $lead->id)
        );
});

test('conversion can match an existing account by company name', function () {
    $rep = leadUser('sales-rep');
    $account = Account::factory()->create([
        'owner_id' => $rep->id,
        'name' => 'Northwind Supplies',
    ]);
    $lead = Lead::factory()->create([
        'owner_id' => $rep->id,
        'company' => 'Northwind Supplies',
        'last_name' => 'Buyer',
    ]);

    $this->actingAs($rep)
        ->post(route('leads.convert', $lead), [
            'account_mode' => 'existing',
            'account_id' => $account->id,
            'create_opportunity' => false,
            'transfer_activities' => false,
        ])
        ->assertRedirect(route('leads.show', $lead));

    $lead->refresh();

    expect($lead->converted_account_id)->toBe($account->id)
        ->and(Contact::query()->whereKey($lead->converted_contact_id)->value('account_id'))->toBe($account->id)
        ->and($lead->converted_opportunity_id)->toBeNull();
});
