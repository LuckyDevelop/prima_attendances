<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$loginPayload = fn (array $override = []) => array_merge([
    'email'       => 'user@example.com',
    'password'    => 'password',
    'device_name' => 'Test Device',
    'device_id'   => 'test-device-uuid',
    'platform'    => 'android',
    'fcm_token'   => null,
], $override);

beforeEach(function () use ($loginPayload) {
    $this->loginPayload = $loginPayload;

    $this->user = User::factory()->create([
        'email'    => 'user@example.com',
        'password' => bcrypt('password'),
    ]);
});

it('returns token and user data on successful login', function () {
    $response = $this->postJson('/api/v1/auth/login', ($this->loginPayload)());

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'token_type',
                'expires_at',
                'user' => [
                    'id', 'employee_id', 'full_name', 'email',
                    'role', 'role_label', 'status',
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'data'    => ['token_type' => 'Bearer'],
        ]);
});

it('returns 401 when email is not registered', function () {
    $response = $this->postJson('/api/v1/auth/login', ($this->loginPayload)([
        'email' => 'notfound@example.com',
    ]));

    $response->assertStatus(401)
        ->assertJson(['success' => false]);
});

it('returns 401 when password is wrong', function () {
    $response = $this->postJson('/api/v1/auth/login', ($this->loginPayload)([
        'password' => 'wrongpassword',
    ]));

    $response->assertStatus(401)
        ->assertJson(['success' => false]);
});

it('returns 403 when user is inactive', function () {
    $this->user->update(['status' => UserStatus::INACTIVE]);

    $response = $this->postJson('/api/v1/auth/login', ($this->loginPayload)());

    $response->assertStatus(403)
        ->assertJson(['success' => false]);
});

it('returns 422 when required fields are missing', function () {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response->assertStatus(422)
        ->assertJsonStructure(['errors' => ['email', 'password', 'device_name', 'device_id', 'platform']]);
});
