<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns leave balances for current year', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $leaveType = LeaveType::factory()->for($company)->create();

    LeaveBalance::factory()->for($user)->for($leaveType)->withQuota(12, 3)->create([
        'year' => now()->year,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/leave-balances');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'total_quota' => 12,
            'used'        => 3,
            'remaining'   => 9,
        ]);
});

it('does not return balances from other years', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $leaveType = LeaveType::factory()->for($company)->create();

    LeaveBalance::factory()->for($user)->for($leaveType)->create([
        'year' => now()->year - 1,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/leave-balances');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('does not return balances of other users', function () {
    $company    = Company::factory()->create();
    $user       = User::factory()->for($company)->create();
    $otherUser  = User::factory()->for($company)->create();
    $leaveType  = LeaveType::factory()->for($company)->create();

    LeaveBalance::factory()->for($otherUser)->for($leaveType)->create(['year' => now()->year]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/leave-balances');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns 401 without token', function () {
    $this->getJson('/api/v1/leave-balances')->assertUnauthorized();
});
