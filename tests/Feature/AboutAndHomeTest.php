<?php

use App\Models\User;

test('guest can view login and the about page', function () {
    $this->get('/login')->assertOk();
    $this->get('/about')->assertOk();
});

test('about page includes the product description from ABOUT.md', function () {
    $this->get('/about')->assertSee('Customer Relationship', false);
});

test('authenticated user can view home', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('home'))->assertOk();
});
