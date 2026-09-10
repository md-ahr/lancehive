<?php

declare(strict_types=1);

use App\Features\Auth\Enums\UserRole;
use App\Features\Auth\Models\User;

it('allows user login with valid credentials', function () {
    $user = User::factory()->freelancer()->create([
        'email' => 'freelancer@example.com',
        'password' => 'password',
    ]);

    $this->postJson($this->apiUrl('login'), [
        'email' => 'freelancer@example.com',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'role'],
        ])
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', 'freelancer@example.com')
        ->assertJsonPath('user.role', UserRole::Freelancer->value);
});

it('fails login with invalid password', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'password',
    ]);

    $this->postJson($this->apiUrl('login'), [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('fails login when user does not exist', function () {
    $this->postJson($this->apiUrl('login'), [
        'email' => 'missing@example.com',
        'password' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires email and password for login', function () {
    $this->postJson($this->apiUrl('login'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('requires valid email format for login', function () {
    $this->postJson($this->apiUrl('login'), [
        'email' => 'not-an-email',
        'password' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
