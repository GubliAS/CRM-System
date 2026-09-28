<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('the sixth failed login is locked out', function () {
    $user = User::factory()->create();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Wrongpass1',
        ]);
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'Password1',
    ]);

    $this->assertGuest();
    expect($response->status())->toBeIn([302, 429]);

    if ($response->status() === 302) {
        $response->assertSessionHasErrors('email');
    }
});

test('a password that matches one of the last five hashes is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'Password1',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ])
        ->assertRedirect('/profile')
        ->assertSessionHasErrors('password');

    expect(Hash::check('Password1', $user->refresh()->password))->toBeTrue();
});

test('a password older than the last five can be reused', function () {
    $user = User::factory()->create();
    $current = 'Password1';

    foreach (['Password2', 'Password3', 'Password4', 'Password5', 'Password6'] as $next) {
        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => $current,
                'password' => $next,
                'password_confirmation' => $next,
            ])
            ->assertSessionHasNoErrors();

        $current = $next;
    }

    expect($user->passwordHistories()->count())->toBe(5);

    $this->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'Password6',
            'password' => 'Password2',
            'password_confirmation' => 'Password2',
        ])
        ->assertSessionHasErrors('password');

    $this->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'Password6',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('Password1', $user->refresh()->password))->toBeTrue();
});

test('session lifetime, reset expiry, and remember duration are configured', function () {
    expect(config('session.lifetime'))->toBe(120)
        ->and(config('auth.passwords.users.expire'))->toBe(60)
        ->and(config('auth.remember_minutes'))->toBe(43200);

    $guard = Auth::guard('web');

    expect($guard)->toBeInstanceOf(SessionGuard::class);

    $rememberDuration = new ReflectionMethod($guard, 'getRememberDuration');

    expect($rememberDuration->invoke($guard))->toBe(43200);
});

test('a password reset notification is sent through the configured mailer', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('registration rejects a password that breaks the policy', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'weak-password@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
});
