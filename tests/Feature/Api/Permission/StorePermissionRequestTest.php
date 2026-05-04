<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\PermissionRequest;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a permission request successfully', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/permissions', [
        'permission_type' => 'late_arrival',
        'request_date'    => now()->addDay()->format('Y-m-d'),
        'start_time'      => '08:00',
        'end_time'        => '10:00',
        'reason'          => 'Keperluan mendadak',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.permission_type.value', 'late_arrival');

    $this->assertDatabaseHas('permission_requests', [
        'user_id'         => $user->id,
        'permission_type' => 'late_arrival',
        'status'          => 'pending',
    ]);
});

it('returns 422 when required fields are missing', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/permissions', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['permission_type', 'request_date']);
});

it('returns 422 when permission_type is invalid', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/permissions', [
        'permission_type' => 'invalid_type',
        'request_date'    => now()->addDay()->format('Y-m-d'),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['permission_type']);
});

it('returns 422 when request_date is in the past', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/permissions', [
        'permission_type' => 'late_arrival',
        'request_date'    => now()->subDay()->format('Y-m-d'),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['request_date']);
});

it('returns 422 when duplicate permission exists for same date and type', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $date = now()->addDay()->format('Y-m-d');

    PermissionRequest::factory()->pending()->for($user)->create([
        'permission_type' => 'late_arrival',
        'request_date'    => $date,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/permissions', [
        'permission_type' => 'late_arrival',
        'request_date'    => $date,
        'reason'          => 'Alasan lain',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('allows different permission types on same date', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $date = now()->addDay()->format('Y-m-d');

    PermissionRequest::factory()->pending()->for($user)->create([
        'permission_type' => 'late_arrival',
        'request_date'    => $date,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/permissions', [
        'permission_type' => 'early_departure',
        'request_date'    => $date,
        'reason'          => 'Keperluan keluarga',
    ]);

    $response->assertCreated();
});

it('returns 401 without token', function () {
    $this->postJson('/api/v1/permissions', [])->assertUnauthorized();
});
