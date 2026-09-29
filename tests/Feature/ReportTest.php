<?php

use App\Models\Lead;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function reportUser(string $slug = 'sales-rep'): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => $slug],
    );

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

test('a private report is hidden from another user', function () {
    $owner = reportUser();
    $other = reportUser();

    $report = Report::factory()->private()->create([
        'name' => 'Owner only pipeline',
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
        'updated_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->get(route('reports.index', ['folder' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->where('reports.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => (int) $row['id'] === $report->id
            ))
        );

    $this->actingAs($other)
        ->get(route('reports.index', ['folder' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->where('reports.data', fn ($rows) => collect($rows)->every(
                fn ($row) => (int) $row['id'] !== $report->id
            ))
        );

    $this->actingAs($other)
        ->get(route('reports.show', $report))
        ->assertForbidden();
});

test('a filter changes the row count', function () {
    $user = reportUser();

    Lead::factory()->count(3)->create([
        'lead_source' => 'Web',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    Lead::factory()->count(2)->create([
        'lead_source' => 'Phone',
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $unfiltered = Report::factory()->private()->create([
        'name' => 'All my leads',
        'object_type' => Report::OBJECT_LEAD,
        'columns' => ['last_name', 'company', 'lead_source'],
        'filters' => [],
        'group_by' => [],
        'chart' => null,
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $filtered = Report::factory()->private()->create([
        'name' => 'Web leads only',
        'object_type' => Report::OBJECT_LEAD,
        'columns' => ['last_name', 'company', 'lead_source'],
        'filters' => [
            ['field' => 'lead_source', 'operator' => 'equals', 'value' => 'Web'],
        ],
        'group_by' => [],
        'chart' => null,
        'owner_id' => $user->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $all = $this->actingAs($user)
        ->get(route('reports.show', $unfiltered))
        ->assertOk()
        ->inertiaProps('result');

    $web = $this->actingAs($user)
        ->get(route('reports.show', $filtered))
        ->assertOk()
        ->inertiaProps('result');

    expect($all['total'])->toBe(5)
        ->and($web['total'])->toBe(3)
        ->and($web['total'])->toBeLessThan($all['total']);
});

test('reports index seeds pre-built public reports', function () {
    $user = reportUser('admin');

    $this->actingAs($user)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->has('reports.data')
        );

    expect(Report::query()->where('is_system', true)->count())->toBeGreaterThanOrEqual(13);
});

test('a user can save a report from the builder', function () {
    $user = reportUser();

    $this->actingAs($user)
        ->post(route('reports.store'), [
            'name' => 'My lead sources',
            'description' => 'Custom builder report',
            'folder' => Report::FOLDER_PRIVATE,
            'object_type' => Report::OBJECT_LEAD,
            'columns' => ['lead_source', 'record_count'],
            'filters' => [],
            'group_by' => ['lead_source'],
            'chart' => [
                'type' => 'bar',
                'label' => 'lead_source',
                'value' => 'record_count',
            ],
        ])
        ->assertRedirect();

    $report = Report::query()->where('name', 'My lead sources')->first();

    expect($report)->not->toBeNull()
        ->and($report->folder)->toBe(Report::FOLDER_PRIVATE)
        ->and($report->owner_id)->toBe($user->id);
});
