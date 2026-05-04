<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\PermissionRequest;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('cancels a pending permission request', function () {
    $company    = Company::factory()->create();
    $user       = User::factory()->for($company)->create();
    $permission = PermissionRequest::factory()->pending()->for($user)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->deleteJson("/api/v1/permissions/{$permission->id}");

    $response->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('permission_requests', ['id' => $permission->id]);
});

it('returns 403 when cancelling another user permission request', function () {
    $company    = Company::factory()->create();
    $user       = User::factory()->for($company)->create();
    $otherUser  = User::factory()->for($company)->create();
    $permission = PermissionRequest::factory()->pending()->for($otherUser)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->deleteJson("/api/v1/permissions/{$permission->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('permission_requests', ['id' => $permission->id]);
});

it('returns 422 when permission request is not pending', function () {
    $company    = Company::factory()->create();
    $user       = User::factory()->for($company)->create();
    $permission = PermissionRequest::factory()->approved()->for($user)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->deleteJson("/api/v1/permissions/{$permission->id}");

    $response->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('returns 404 when permission request does not exist', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->deleteJson('/api/v1/permissions/99999');

    $response->assertNotFound();
});

it('returns 401 without token', function () {
    $this->deleteJson('/api/v1/permissions/1')->assertUnauthorized();
});
