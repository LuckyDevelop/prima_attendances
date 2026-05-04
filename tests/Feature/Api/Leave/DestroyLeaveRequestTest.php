<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('cancels a pending leave request', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $leaveType = LeaveType::factory()->for($company)->create();

    $leave = LeaveRequest::factory()->pending()->for($user)->for($leaveType)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->deleteJson("/api/v1/leaves/{$leave->id}");

    $response->assertOk()
        ->assertJsonPath('success', true);

    $this->assertSoftDeleted('leave_requests', ['id' => $leave->id]);
});

it('returns 403 when cancelling another user leave request', function () {
    $company    = Company::factory()->create();
    $user       = User::factory()->for($company)->create();
    $otherUser  = User::factory()->for($company)->create();
    $leaveType  = LeaveType::factory()->for($company)->create();

    $leave = LeaveRequest::factory()->pending()->for($otherUser)->for($leaveType)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->deleteJson("/api/v1/leaves/{$leave->id}");

    $response->assertForbidden();
});

it('returns 422 when leave request is not pending', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $leaveType = LeaveType::factory()->for($company)->create();

    $leave = LeaveRequest::factory()->approved()->for($user)->for($leaveType)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->deleteJson("/api/v1/leaves/{$leave->id}");

    $response->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('returns 404 when leave request does not exist', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->deleteJson('/api/v1/leaves/99999');

    $response->assertNotFound();
});

it('returns 401 without token', function () {
    $this->deleteJson('/api/v1/leaves/1')->assertUnauthorized();
});
