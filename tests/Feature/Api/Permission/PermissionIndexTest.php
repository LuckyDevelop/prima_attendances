<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\PermissionRequest;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns paginated list of own permission requests', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    PermissionRequest::factory()->count(3)->for($user)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/permissions');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});

it('does not return permission requests of other users', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $otherUser = User::factory()->for($company)->create();

    PermissionRequest::factory()->count(2)->for($otherUser)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/permissions');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('filters by status', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    PermissionRequest::factory()->pending()->for($user)->create();
    PermissionRequest::factory()->approved()->for($user)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/permissions?status=pending');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filters by permission type', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    PermissionRequest::factory()->lateArrival()->for($user)->create();
    PermissionRequest::factory()->for($user)->create(['permission_type' => 'early_departure']);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/permissions?type=late_arrival');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns 401 without token', function () {
    $this->getJson('/api/v1/permissions')->assertUnauthorized();
});
