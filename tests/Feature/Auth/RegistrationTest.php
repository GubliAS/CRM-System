<?php

use App\Models\Role;
use App\Models\User;

function seedRegistrationRoles(): void
{
    foreach ([
        ['name' => 'Administrator', 'slug' => 'admin'],
        ['name' => 'Sales Representative', 'slug' => 'sales-rep'],
    ] as $definition) {
        Role::query()->firstOrCreate(
            ['slug' => $definition['slug']],
            ['name' => $definition['name']],
        );
    }
}

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('the first registered user becomes administrator', function () {
    seedRegistrationRoles();

    $response = $this->post('/register', [
        'name' => 'First User',
        'email' => 'first@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));

    $user = User::query()->where('email', 'first@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role_id)->not->toBeNull()
        ->and($user->role?->slug)->toBe('admin');
});

test('later registrations become sales representatives', function () {
    seedRegistrationRoles();

    User::factory()->create([
        'role_id' => Role::query()->where('slug', 'admin')->value('id'),
    ]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role_id)->not->toBeNull()
        ->and($user->role?->slug)->toBe('sales-rep');
});

test('registration fails clearly when roles are missing', function () {
    $response = $this->from('/register')->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $this->assertGuest();
    $response->assertRedirect('/register');
    $response->assertSessionHasErrors('email');
});
