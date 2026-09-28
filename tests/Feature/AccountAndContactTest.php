<?php

use App\Models\Account;
use App\Models\Contact;
use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function crmUser(string $slug): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $slug],
        ['name' => $slug],
    );

    return User::factory()->create([
        'role_id' => $role->id,
    ]);
}

test('a sales rep can create an account and a contact on that account', function () {
    $rep = crmUser('sales-rep');
    $other = crmUser('sales-rep');

    $response = $this->actingAs($rep)->post(route('accounts.store'), [
        'name' => 'Northwind',
        'phone' => '555-0100',
        'industry' => 'Wholesale',
        'owner_id' => $other->id,
    ]);

    $account = Account::query()->where('name', 'Northwind')->first();

    expect($account)->not->toBeNull()
        ->and($account->owner_id)->toBe($rep->id)
        ->and($account->created_by)->toBe($rep->id);

    $response->assertRedirect(route('accounts.show', $account));

    $contactResponse = $this->actingAs($rep)->post(route('contacts.store'), [
        'last_name' => 'Demo',
        'first_name' => 'Alex',
        'account_id' => $account->id,
        'email' => 'alex@example.com',
        'owner_id' => $other->id,
    ]);

    $contact = Contact::query()->where('last_name', 'Demo')->first();

    expect($contact)->not->toBeNull()
        ->and($contact->account_id)->toBe($account->id)
        ->and($contact->owner_id)->toBe($rep->id);

    $contactResponse->assertRedirect(route('contacts.show', $contact));

    $this->actingAs($rep)
        ->get(route('accounts.show', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Accounts/Show')
            ->where('account.contacts.0.id', $contact->id)
        );
});

test('another sales rep cannot see those records and a manager can', function () {
    $rep = crmUser('sales-rep');
    $other = crmUser('sales-rep');
    $manager = crmUser('sales-manager');

    $account = Account::factory()->create([
        'owner_id' => $rep->id,
        'name' => 'Hidden Account',
    ]);
    $contact = Contact::factory()->create([
        'account_id' => $account->id,
        'owner_id' => $rep->id,
        'last_name' => 'Hidden',
    ]);

    $visibleAccount = Account::factory()->create([
        'owner_id' => $other->id,
        'name' => 'Visible Account',
    ]);
    $visibleContact = Contact::factory()->create([
        'account_id' => $visibleAccount->id,
        'owner_id' => $other->id,
        'last_name' => 'Visible',
    ]);

    $this->actingAs($other)
        ->get(route('accounts.show', $account))
        ->assertForbidden();

    $this->actingAs($other)
        ->get(route('contacts.show', $contact))
        ->assertForbidden();

    $this->actingAs($other)
        ->get(route('accounts.index', ['view' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Accounts/Index')
            ->where('accounts.total', 1)
            ->where('accounts.data.0.id', $visibleAccount->id)
            ->where('accounts.data', fn ($rows) => $rows->pluck('id')->doesntContain($account->id))
        );

    $this->actingAs($other)
        ->get(route('contacts.index', ['view' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Contacts/Index')
            ->where('contacts.total', 1)
            ->where('contacts.data.0.id', $visibleContact->id)
            ->where('contacts.data', fn ($rows) => $rows->pluck('id')->doesntContain($contact->id))
        );

    $this->actingAs($manager)
        ->get(route('accounts.show', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Accounts/Show')->where('account.id', $account->id));

    $this->actingAs($manager)
        ->get(route('contacts.show', $contact))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Contacts/Show')->where('contact.id', $contact->id));
});

test('a service rep can view an account but cannot create update or delete it', function () {
    $owner = crmUser('sales-rep');
    $service = crmUser('service-rep');
    $reader = crmUser('read-only');
    $account = Account::factory()->create([
        'owner_id' => $owner->id,
        'name' => 'Shared Account',
    ]);

    $this->actingAs($service)
        ->get(route('accounts.show', $account))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.update', false)
            ->where('can.delete', false)
            ->where('can.createContact', false)
        );

    $this->actingAs($service)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('can.create', false));

    $this->actingAs($service)
        ->post(route('accounts.store'), ['name' => 'Blocked'])
        ->assertForbidden();

    $this->actingAs($service)
        ->put(route('accounts.update', $account), ['name' => 'Changed'])
        ->assertForbidden();

    $this->actingAs($service)
        ->delete(route('accounts.destroy', $account))
        ->assertForbidden();

    $this->actingAs($reader)
        ->get(route('accounts.show', $account))
        ->assertOk();

    $this->actingAs($reader)
        ->post(route('accounts.store'), ['name' => 'Blocked'])
        ->assertForbidden();

    $this->actingAs($reader)
        ->put(route('accounts.update', $account), ['name' => 'Changed'])
        ->assertForbidden();

    $this->actingAs($reader)
        ->delete(route('accounts.destroy', $account))
        ->assertForbidden();

    expect($account->fresh()->name)->toBe('Shared Account')
        ->and(Account::query()->where('name', 'Blocked')->exists())->toBeFalse();
});

test('validation errors use the account and contact field keys', function () {
    $rep = crmUser('sales-rep');

    $this->actingAs($rep)
        ->from(route('accounts.create'))
        ->post(route('accounts.store'), ['name' => ''])
        ->assertRedirect(route('accounts.create'))
        ->assertSessionHasErrors('name');

    $this->actingAs($rep)
        ->get(route('accounts.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Accounts/Create')
            ->where('errors.name', fn ($error) => is_string($error) && $error !== '')
        );

    $this->actingAs($rep)
        ->from(route('contacts.create'))
        ->post(route('contacts.store'), [])
        ->assertRedirect(route('contacts.create'))
        ->assertSessionHasErrors(['last_name', 'account_id']);

    $this->actingAs($rep)
        ->get(route('contacts.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Contacts/Create')
            ->where('errors.last_name', fn ($error) => is_string($error) && $error !== '')
            ->where('errors.account_id', fn ($error) => is_string($error) && $error !== '')
        );
});

test('a sales rep cannot reassign the owner', function () {
    $rep = crmUser('sales-rep');
    $other = crmUser('sales-rep');
    $manager = crmUser('sales-manager');
    $admin = crmUser('admin');

    $account = Account::factory()->create([
        'owner_id' => $rep->id,
        'name' => 'Owned Account',
    ]);
    $contact = Contact::factory()->create([
        'account_id' => $account->id,
        'owner_id' => $rep->id,
        'last_name' => 'Owned',
    ]);

    $this->actingAs($rep)
        ->put(route('accounts.update', $account), [
            'name' => 'Renamed Account',
            'owner_id' => $other->id,
        ])
        ->assertSessionHasErrors('owner_id');

    $this->actingAs($rep)
        ->put(route('contacts.update', $contact), [
            'last_name' => 'Renamed',
            'account_id' => $account->id,
            'owner_id' => $other->id,
        ])
        ->assertSessionHasErrors('owner_id');

    expect($account->fresh()->owner_id)->toBe($rep->id)
        ->and($account->fresh()->name)->toBe('Owned Account')
        ->and($contact->fresh()->owner_id)->toBe($rep->id)
        ->and($contact->fresh()->last_name)->toBe('Owned');

    $this->actingAs($rep)
        ->put(route('accounts.update', $account), ['name' => 'Renamed Account'])
        ->assertRedirect(route('accounts.show', $account));

    expect($account->fresh()->name)->toBe('Renamed Account')
        ->and($account->fresh()->owner_id)->toBe($rep->id);

    $this->actingAs($manager)
        ->put(route('accounts.update', $account), [
            'name' => 'Renamed Account',
            'owner_id' => $other->id,
        ])
        ->assertRedirect(route('accounts.show', $account));

    expect($account->fresh()->owner_id)->toBe($other->id);

    $this->actingAs($admin)
        ->put(route('contacts.update', $contact), [
            'last_name' => 'Owned',
            'account_id' => $account->id,
            'owner_id' => $manager->id,
        ])
        ->assertRedirect(route('contacts.show', $contact));

    expect($contact->fresh()->owner_id)->toBe($manager->id);
});

test('account lists cap page size and filter by the whitelisted columns', function () {
    $rep = crmUser('sales-rep');

    Account::factory()->create([
        'owner_id' => $rep->id,
        'name' => 'Alpha Co',
        'industry' => 'Retail',
    ]);
    Account::factory()->create([
        'owner_id' => $rep->id,
        'name' => 'Zulu Co',
        'industry' => 'Logistics',
    ]);

    $this->actingAs($rep)
        ->get(route('accounts.index', [
            'sort' => 'name',
            'direction' => 'desc',
            'per_page' => 500,
            'search' => 'Zulu',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.per_page', 200)
            ->where('filters.sort', 'name')
            ->where('filters.direction', 'desc')
            ->where('accounts.total', 1)
            ->where('accounts.data.0.name', 'Zulu Co')
        );

    $this->actingAs($rep)
        ->get(route('accounts.index', ['view' => 'all', 'sort' => 'not-a-column']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'name')
            ->where('accounts.data.0.name', 'Alpha Co')
        );

    $alpha = Account::query()->where('name', 'Alpha Co')->first();
    Contact::factory()->create([
        'account_id' => $alpha->id,
        'owner_id' => $rep->id,
        'first_name' => 'Alex',
        'last_name' => 'Demo',
        'email' => 'alex@example.com',
    ]);
    Contact::factory()->create([
        'account_id' => Account::query()->where('name', 'Zulu Co')->value('id'),
        'owner_id' => $rep->id,
        'first_name' => 'Sam',
        'last_name' => 'Other',
    ]);

    $this->actingAs($rep)
        ->get(route('contacts.index', [
            'search' => 'Alex Demo',
            'sort' => 'account',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('contacts.total', 1)
            ->where('contacts.data.0.last_name', 'Demo')
            ->where('filters.sort', 'account')
        );
});

test('an account with contacts cannot be deleted and a contact can', function () {
    $rep = crmUser('sales-rep');
    $account = Account::factory()->create([
        'owner_id' => $rep->id,
        'name' => 'Parent Co',
    ]);
    $contact = Contact::factory()->create([
        'account_id' => $account->id,
        'owner_id' => $rep->id,
    ]);

    $this->actingAs($rep)
        ->from(route('accounts.show', $account))
        ->delete(route('accounts.destroy', $account))
        ->assertRedirect(route('accounts.show', $account))
        ->assertSessionHasErrors('delete');

    expect(Account::query()->whereKey($account->id)->exists())->toBeTrue();

    $this->actingAs($rep)
        ->delete(route('contacts.destroy', $contact))
        ->assertRedirect(route('contacts.index'));

    expect(Contact::query()->whereKey($contact->id)->exists())->toBeFalse();

    $this->actingAs($rep)
        ->delete(route('accounts.destroy', $account))
        ->assertRedirect(route('accounts.index'));

    expect(Account::query()->whereKey($account->id)->exists())->toBeFalse();
});
