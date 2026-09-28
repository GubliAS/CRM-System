<?php

use App\Models\Role;
use App\Models\SupportCase;
use App\Models\User;

function caseUser(string $slug): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => $slug],
    );

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

test('closing a case locks edits until reopen', function () {
    $service = caseUser('service-rep');
    $case = SupportCase::factory()->create([
        'owner_id' => $service->id,
        'created_by' => $service->id,
        'updated_by' => $service->id,
        'subject' => 'Open billing question',
        'status' => 'Working',
        'origin' => 'Phone',
    ]);

    $this->actingAs($service)
        ->post(route('cases.close', $case))
        ->assertRedirect(route('cases.show', $case));

    $case->refresh();

    expect($case->status)->toBe('Closed')
        ->and($case->is_closed)->toBeTrue()
        ->and($case->closed_at)->not->toBeNull();

    $this->actingAs($service)
        ->get(route('cases.edit', $case))
        ->assertForbidden();

    $this->actingAs($service)
        ->put(route('cases.update', $case), [
            'subject' => 'Should not save',
            'status' => 'Working',
            'origin' => 'Phone',
        ])
        ->assertForbidden();

    $this->actingAs($service)
        ->post(route('cases.status', $case), [
            'status' => 'Escalated',
        ])
        ->assertForbidden();

    expect($case->fresh()->subject)->toBe('Open billing question')
        ->and($case->fresh()->status)->toBe('Closed');

    $this->actingAs($service)
        ->post(route('cases.reopen', $case))
        ->assertRedirect(route('cases.show', $case));

    $case->refresh();

    expect($case->status)->toBe('Working')
        ->and($case->is_closed)->toBeFalse()
        ->and($case->closed_at)->toBeNull();

    $this->actingAs($service)
        ->put(route('cases.update', $case), [
            'subject' => 'Reopened and edited',
            'status' => 'Working',
            'origin' => 'Email',
        ])
        ->assertRedirect(route('cases.show', $case));

    expect($case->fresh()->subject)->toBe('Reopened and edited')
        ->and($case->fresh()->origin)->toBe('Email');
});

test('a sales rep cannot update a case', function () {
    $service = caseUser('service-rep');
    $sales = caseUser('sales-rep');

    $case = SupportCase::factory()->create([
        'owner_id' => $service->id,
        'created_by' => $service->id,
        'updated_by' => $service->id,
        'subject' => 'Service case',
        'status' => 'New',
        'origin' => 'Chat',
    ]);

    $this->actingAs($sales)
        ->put(route('cases.update', $case), [
            'subject' => 'Guessed URL edit',
            'status' => 'Working',
            'origin' => 'Chat',
        ])
        ->assertForbidden();

    $this->actingAs($sales)
        ->get(route('cases.edit', $case))
        ->assertForbidden();

    $this->actingAs($sales)
        ->post(route('cases.close', $case))
        ->assertForbidden();

    expect($case->fresh()->subject)->toBe('Service case')
        ->and($case->fresh()->status)->toBe('New');
});

test('a service rep can create a case with a generated case number', function () {
    $service = caseUser('service-rep');

    $response = $this->actingAs($service)->post(route('cases.store'), [
        'subject' => 'Web form question',
        'status' => 'New',
        'origin' => 'Web',
        'priority' => 'High',
    ]);

    $case = SupportCase::query()->where('subject', 'Web form question')->first();

    expect($case)->not->toBeNull()
        ->and($case->case_number)->toBe(SupportCase::numberFromId($case->id))
        ->and($case->owner_id)->toBe($service->id)
        ->and($case->origin)->toBe('Web');

    $response->assertRedirect(route('cases.show', $case));
});
