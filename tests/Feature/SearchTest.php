<?php

use App\Models\Lead;
use App\Models\RecentlyViewedRecord;
use App\Models\Role;
use App\Models\SearchHistory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function searchUser(string $slug): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => $slug],
    );

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

test('a sales rep does not see another reps private lead in search suggest or results', function () {
    $rep = searchUser('sales-rep');
    $other = searchUser('sales-rep');

    $ownLead = Lead::factory()->create([
        'owner_id' => $rep->id,
        'first_name' => 'Own',
        'last_name' => 'Alpha',
        'company' => 'SharedSearchCo',
        'email' => 'own-alpha@example.com',
    ]);

    $privateLead = Lead::factory()->create([
        'owner_id' => $other->id,
        'first_name' => 'Hidden',
        'last_name' => 'Beta',
        'company' => 'SharedSearchCo',
        'email' => 'hidden-beta@example.com',
    ]);

    $this->actingAs($rep)
        ->getJson(route('search.suggest', ['q' => 'SharedSearch']))
        ->assertOk()
        ->assertJsonPath('results.leads', function ($leads) use ($ownLead, $privateLead) {
            $ids = collect($leads)->pluck('id');

            return $ids->contains($ownLead->id) && ! $ids->contains($privateLead->id);
        });

    $this->actingAs($rep)
        ->get(route('search.index', ['q' => 'SharedSearch', 'type' => 'leads']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Search/Index')
            ->where('results.total', 1)
            ->where('results.data.0.id', $ownLead->id)
            ->where('results.data', fn ($rows) => collect($rows)->pluck('id')->doesntContain($privateLead->id))
        );

    $this->actingAs($other)
        ->getJson(route('search.suggest', ['q' => 'SharedSearch']))
        ->assertOk()
        ->assertJsonPath('results.leads', function ($leads) use ($ownLead, $privateLead) {
            $ids = collect($leads)->pluck('id');

            return $ids->contains($privateLead->id) && ! $ids->contains($ownLead->id);
        });
});

test('search history is stored for the current user', function () {
    $rep = searchUser('sales-rep');

    Lead::factory()->create([
        'owner_id' => $rep->id,
        'company' => 'HistoryProbe Inc',
        'last_name' => 'Probe',
    ]);

    $this->actingAs($rep)
        ->get(route('search.index', ['q' => 'HistoryProbe']))
        ->assertOk();

    expect(SearchHistory::query()->where('user_id', $rep->id)->where('query', 'HistoryProbe')->exists())->toBeTrue();
});

test('opening a lead records recently viewed and defaults the list to those records', function () {
    $rep = searchUser('sales-rep');

    $viewed = Lead::factory()->create([
        'owner_id' => $rep->id,
        'last_name' => 'Viewed',
        'company' => 'Recent Co',
    ]);
    $unviewed = Lead::factory()->create([
        'owner_id' => $rep->id,
        'last_name' => 'Unseen',
        'company' => 'Recent Co',
    ]);

    $this->actingAs($rep)
        ->get(route('leads.show', $viewed))
        ->assertOk();

    expect(RecentlyViewedRecord::query()
        ->where('user_id', $rep->id)
        ->where('viewable_type', Lead::class)
        ->where('viewable_id', $viewed->id)
        ->exists())->toBeTrue();

    $this->actingAs($rep)
        ->get(route('leads.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Leads/Index')
            ->where('filters.view', 'recent')
            ->where('leads.total', 1)
            ->where('leads.data.0.id', $viewed->id)
            ->where('leads.data', fn ($rows) => collect($rows)->pluck('id')->doesntContain($unviewed->id))
        );

    $this->actingAs($rep)
        ->get(route('leads.index', ['view' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('leads.total', 2)
        );
});
