<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns paginated list of own leave requests', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $leaveType = LeaveType::factory()->for($company)->create();

    LeaveRequest::factory()->count(3)
        ->for($user)
        ->for($leaveType)
        ->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/leaves');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});

it('does not return leave requests of other users', function () {
    $company    = Company::factory()->create();
    $user       = User::factory()->for($company)->create();
    $otherUser  = User::factory()->for($company)->create();
    $leaveType  = LeaveType::factory()->for($company)->create();

    LeaveRequest::factory()->count(2)->for($otherUser)->for($leaveType)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/leaves');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('filters by status', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $leaveType = LeaveType::factory()->for($company)->create();

    LeaveRequest::factory()->pending()->for($user)->for($leaveType)->create();
    LeaveRequest::factory()->approved()->for($user)->for($leaveType)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/leaves?status=pending');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns 401 without token', function () {
    $this->getJson('/api/v1/leaves')->assertUnauthorized();
});
