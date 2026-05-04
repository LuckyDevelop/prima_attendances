<?php

declare(strict_types=1);

use Laravel\Sanctum\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('revokes token on logout', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;

    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(1);

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Logout berhasil.']);

    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(0);
});

it('returns 401 without token', function () {
    $this->postJson('/api/v1/auth/logout')
        ->assertStatus(401);
});

it('returns 401 with invalid token', function () {
    $this->withToken('invalid-token-string')
        ->postJson('/api/v1/auth/logout')
        ->assertStatus(401);
});
