<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('can register a new user', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated();

    $response->assertJsonStructure([
        'token',
    ]);

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect(Hash::check('password', $user->password))->toBeTrue();
});

it('can login an existing user', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'token',
    ]);
});

it('validates registration input', function () {
    $response = $this->postJson('/api/register', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'email', 'password']);
});
