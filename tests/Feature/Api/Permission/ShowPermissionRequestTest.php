<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\PermissionRequest;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns own permission request detail', function () {
    $company    = Company::factory()->create();
    $user       = User::factory()->for($company)->create();
    $permission = PermissionRequest::factory()->for($user)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson("/api/v1/permissions/{$permission->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $permission->id)
        ->assertJsonStructure(['data' => ['permission_type' => ['value', 'label']]]);
});

it('returns 403 when viewing another user permission request', function () {
    $company    = Company::factory()->create();
    $user       = User::factory()->for($company)->create();
    $otherUser  = User::factory()->for($company)->create();
    $permission = PermissionRequest::factory()->for($otherUser)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson("/api/v1/permissions/{$permission->id}");

    $response->assertForbidden();
});

it('returns 404 when permission request does not exist', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/permissions/99999');

    $response->assertNotFound();
});

it('returns 401 without token', function () {
    $this->getJson('/api/v1/permissions/1')->assertUnauthorized();
});
