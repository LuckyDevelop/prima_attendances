<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns authenticated user data', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data'    => [
                'id'        => $user->id,
                'full_name' => $user->full_name,
                'email'     => $user->email,
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'id', 'employee_id', 'full_name', 'email',
                'role', 'role_label', 'status',
                'department', 'company', 'office_location',
            ],
        ]);
});

it('returns 401 without token', function () {
    $this->getJson('/api/v1/auth/me')
        ->assertStatus(401);
});

it('returns 401 with invalid token', function () {
    $this->withToken('invalid-token-string')
        ->getJson('/api/v1/auth/me')
        ->assertStatus(401);
});
