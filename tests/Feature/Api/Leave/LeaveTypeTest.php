<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\LeaveType;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns list of leave types for user company', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    LeaveType::factory()->count(3)->for($company)->create();
    LeaveType::factory()->create(); // other company

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/leave-types');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data');
});

it('returns empty array when company has no leave types', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/leave-types');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns 401 without token', function () {
    $this->getJson('/api/v1/leave-types')->assertUnauthorized();
});

it('returns correct leave type fields', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    LeaveType::factory()->for($company)->create([
        'name'                => 'Cuti Tahunan',
        'default_quota'       => 12,
        'requires_attachment' => false,
        'is_paid'             => true,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/leave-types');

    $response->assertOk()
        ->assertJsonFragment([
            'name'                => 'Cuti Tahunan',
            'default_quota'       => 12,
            'requires_attachment' => false,
            'is_paid'             => true,
        ]);
});
