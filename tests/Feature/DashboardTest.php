<?php

use App\Models\Dashboard;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function dashboardUser(string $slug = 'sales-rep'): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => $slug],
    );

    return User::factory()->create(['role_id' => $role->id]);
}

function dashboardFor(User $owner, array $attributes = []): Dashboard
{
    return Dashboard::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'created_by' => $owner->id,
        'updated_by' => $owner->id,
    ], $attributes));
}

test('a private dashboard is hidden from another user', function () {
    $owner = dashboardUser();
    $other = dashboardUser();
    $dashboard = dashboardFor($owner, ['name' => 'Owner only']);

    $this->actingAs($other)
        ->get(route('dashboards.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboards.data', fn ($rows) => collect($rows)->doesntContain(
                fn ($row) => (int) $row['id'] === $dashboard->id
            ))
        );

    $this->actingAs($other)
        ->get(route('dashboards.show', $dashboard))
        ->assertForbidden();
});

test('a shared dashboard is listed and viewable by others but not editable', function () {
    $owner = dashboardUser();
    $other = dashboardUser();
    $dashboard = dashboardFor($owner, [
        'name' => 'Team pipeline',
        'folder' => Dashboard::FOLDER_SHARED,
    ]);

    $this->actingAs($other)
        ->get(route('dashboards.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboards.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => (int) $row['id'] === $dashboard->id
                    && $row['folder'] === 'shared'
                    && $row['is_owner'] === false
                    && $row['can']['update'] === false
            ))
        );

    $this->actingAs($other)
        ->get(route('dashboards.show', $dashboard))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboards/Show')
            ->where('can.update', false)
            ->where('can.delete', false)
            ->where('can.clone', true)
        );

    $this->actingAs($other)->get(route('dashboards.edit', $dashboard))->assertForbidden();
    $this->actingAs($other)->delete(route('dashboards.destroy', $dashboard))->assertForbidden();
    $this->actingAs($other)
        ->put(route('dashboards.update', $dashboard), ['name' => 'Hijacked'])
        ->assertForbidden();

    expect($dashboard->fresh()->name)->toBe('Team pipeline');
});

test('a read-only user cannot see a shared dashboard they may not list', function () {
    $owner = dashboardUser();
    $outsider = dashboardUser('unknown-role');
    $dashboard = dashboardFor($owner, ['folder' => Dashboard::FOLDER_SHARED]);

    $this->actingAs($outsider)
        ->get(route('dashboards.show', $dashboard))
        ->assertForbidden();
});

test('creating a dashboard saves its folder, and defaults to private', function () {
    $user = dashboardUser();

    $this->actingAs($user)
        ->post(route('dashboards.store'), [
            'name' => 'Shared one',
            'folder' => 'shared',
            'widgets' => [],
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('dashboards.store'), ['name' => 'Default folder', 'widgets' => []])
        ->assertRedirect();

    expect(Dashboard::query()->where('name', 'Shared one')->value('folder'))->toBe('shared')
        ->and(Dashboard::query()->where('name', 'Default folder')->value('folder'))->toBe('private');
});

test('an unknown folder is rejected', function () {
    $user = dashboardUser();

    $this->actingAs($user)
        ->post(route('dashboards.store'), ['name' => 'Bad', 'folder' => 'public', 'widgets' => []])
        ->assertSessionHasErrors('folder');
});

test('updating keeps the folder unless a new one is sent', function () {
    $user = dashboardUser();
    $dashboard = dashboardFor($user, ['folder' => Dashboard::FOLDER_SHARED]);

    $this->actingAs($user)
        ->put(route('dashboards.update', $dashboard), ['name' => 'Renamed', 'widgets' => []])
        ->assertRedirect();

    expect($dashboard->fresh()->folder)->toBe('shared');

    $this->actingAs($user)
        ->put(route('dashboards.update', $dashboard), ['name' => 'Renamed', 'folder' => 'private', 'widgets' => []])
        ->assertRedirect();

    expect($dashboard->fresh()->folder)->toBe('private');
});

test('cloning a shared dashboard gives the copy to the cloner as private', function () {
    $owner = dashboardUser();
    $other = dashboardUser();
    $dashboard = dashboardFor($owner, ['folder' => Dashboard::FOLDER_SHARED, 'name' => 'Source']);

    $this->actingAs($other)->post(route('dashboards.clone', $dashboard))->assertRedirect();

    $copy = Dashboard::query()->where('name', 'Copy of Source')->firstOrFail();

    expect((int) $copy->owner_id)->toBe($other->id)
        ->and($copy->folder)->toBe('private');
});

test('the list carries a layout preview for each card', function () {
    $user = dashboardUser();
    dashboardFor($user, [
        'widgets' => [
            ['id' => 'a', 'report_id' => 1, 'type' => 'metric', 'row' => 0, 'col' => 0, 'width' => 4, 'height' => 2],
            ['id' => 'b', 'report_id' => 1, 'type' => 'chart', 'row' => 0, 'col' => 4, 'width' => 8, 'height' => 4],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('dashboards.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboards.data.0.preview', [
                ['type' => 'metric', 'row' => 0, 'col' => 0, 'width' => 4, 'height' => 2],
                ['type' => 'chart', 'row' => 0, 'col' => 4, 'width' => 8, 'height' => 4],
            ])
        );
});

test('the builder can preview a widget with the same shape the viewer renders', function () {
    $user = dashboardUser();
    $report = \App\Models\Report::factory()->public()->create();

    $this->actingAs($user)
        ->postJson(route('dashboards.preview-widget'), ['report_id' => $report->id, 'type' => 'table'])
        ->assertOk()
        ->assertJsonPath('type', 'table')
        ->assertJsonPath('error', null)
        ->assertJsonPath('report_name', $report->name)
        ->assertJsonStructure(['result' => ['columns', 'rows', 'total']]);

    $this->actingAs($user)
        ->postJson(route('dashboards.preview-widget'), ['report_id' => $report->id, 'type' => 'metric'])
        ->assertOk()
        ->assertJsonStructure(['result' => ['value', 'label']]);
});

test('previewing a report the user cannot view returns an unavailable widget, not data', function () {
    $user = dashboardUser();
    $private = \App\Models\Report::factory()->private()->create();

    $this->actingAs($user)
        ->postJson(route('dashboards.preview-widget'), ['report_id' => $private->id, 'type' => 'table'])
        ->assertOk()
        ->assertJsonPath('error', 'Report is unavailable.')
        ->assertJsonPath('result', null);
});

test('previewing needs the right to build dashboards and a valid type', function () {
    $reader = dashboardUser('read-only');
    $user = dashboardUser();
    $report = \App\Models\Report::factory()->public()->create();

    $this->actingAs($reader)
        ->postJson(route('dashboards.preview-widget'), ['report_id' => $report->id, 'type' => 'table'])
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson(route('dashboards.preview-widget'), ['report_id' => $report->id, 'type' => 'pie'])
        ->assertStatus(422);
});
