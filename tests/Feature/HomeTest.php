<?php

use App\Models\User;

test('guest can view login', function () {
    $this->get('/login')->assertOk();
});

test('authenticated user can view home', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('home'))->assertOk();
});
