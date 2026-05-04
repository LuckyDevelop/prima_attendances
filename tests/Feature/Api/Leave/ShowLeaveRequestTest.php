<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns own leave request detail', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $leaveType = LeaveType::factory()->for($company)->create();

    $leave = LeaveRequest::factory()->for($user)->for($leaveType)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson("/api/v1/leaves/{$leave->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $leave->id)
        ->assertJsonPath('data.status', 'pending');
});

it('returns 403 when viewing another user leave request', function () {
    $company    = Company::factory()->create();
    $user       = User::factory()->for($company)->create();
    $otherUser  = User::factory()->for($company)->create();
    $leaveType  = LeaveType::factory()->for($company)->create();

    $leave = LeaveRequest::factory()->for($otherUser)->for($leaveType)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson("/api/v1/leaves/{$leave->id}");

    $response->assertForbidden();
});

it('returns 404 when leave request does not exist', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/leaves/99999');

    $response->assertNotFound();
});

it('returns 401 without token', function () {
    $this->getJson('/api/v1/leaves/1')->assertUnauthorized();
});
