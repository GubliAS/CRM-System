<?php

use App\Models\Account;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Role;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function userWithRole(string $slug): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => $slug],
    );

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

test('a sales rep cannot open another reps lead and can open their own', function () {
    $rep = userWithRole('sales-rep');
    $otherRep = userWithRole('sales-rep');
    $manager = userWithRole('sales-manager');

    $ownLead = Lead::factory()->create(['owner_id' => $rep->id]);
    $otherLead = Lead::factory()->create(['owner_id' => $otherRep->id]);

    $this->actingAs($rep)
        ->get(route('leads.show', $otherLead))
        ->assertForbidden();

    $this->actingAs($rep)
        ->get(route('leads.show', $ownLead))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Leads/Show')
            ->where('lead.id', $ownLead->id)
        );

    $this->actingAs($manager)
        ->get(route('leads.show', $otherLead))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Leads/Show')
            ->where('lead.id', $otherLead->id)
        );

    expect(Lead::query()->visibleTo($rep)->whereKey($otherLead->id)->exists())->toBeFalse()
        ->and(Lead::query()->visibleTo($rep)->whereKey($ownLead->id)->exists())->toBeTrue()
        ->and(Lead::query()->visibleTo($manager)->whereKey($otherLead->id)->exists())->toBeTrue();
});

test('a user with no role cannot open accounts index and sees the app 403 page', function () {
    $user = User::factory()->create(['role_id' => null]);

    $this->actingAs($user)
        ->get(route('accounts.index'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Errors/Forbidden')
            ->where('missingRole', true)
            ->where('message', 'You do not have access to this page.')
        );
});

test('each role is denied the records it must not touch', function () {
    $reader = userWithRole('read-only');
    $service = userWithRole('service-rep');
    $manager = userWithRole('sales-manager');
    $owner = userWithRole('sales-rep');
    $admin = userWithRole('admin');

    $account = Account::factory()->create(['owner_id' => $owner->id]);
    $task = Task::factory()->create([
        'owner_id' => $owner->id,
        'assigned_to_id' => $owner->id,
    ]);
    $case = SupportCase::factory()->create(['owner_id' => $owner->id]);
    $privateEvent = Event::factory()->create([
        'owner_id' => $owner->id,
        'assigned_to_id' => $owner->id,
        'is_private' => true,
    ]);

    expect($reader->can('create', Lead::class))->toBeFalse()
        ->and($service->can('update', $account))->toBeFalse()
        ->and($service->can('view', $task))->toBeFalse()
        ->and($manager->can('view', $case))->toBeFalse()
        ->and($service->can('view', $case))->toBeTrue()
        ->and($manager->can('view', $privateEvent))->toBeFalse()
        ->and($owner->can('view', $privateEvent))->toBeTrue()
        ->and($admin->can('view', $case))->toBeTrue()
        ->and($admin->can('view', $privateEvent))->toBeTrue();

    expect(Event::query()->visibleTo($manager)->whereKey($privateEvent->id)->exists())->toBeFalse()
        ->and(SupportCase::query()->visibleTo($manager)->whereKey($case->id)->exists())->toBeFalse()
        ->and(SupportCase::query()->visibleTo($service)->whereKey($case->id)->exists())->toBeTrue();
});
