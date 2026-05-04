<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a leave request with sufficient balance', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $leaveType = LeaveType::factory()->for($company)->create([
        'default_quota'       => 12,
        'requires_attachment' => false,
    ]);

    LeaveBalance::factory()->for($user)->for($leaveType)->withQuota(12, 0)->create([
        'year' => now()->year,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/leaves', [
        'leave_type_id' => $leaveType->id,
        'start_date'    => now()->nextWeekday()->format('Y-m-d'),
        'end_date'      => now()->nextWeekday()->format('Y-m-d'),
        'reason'        => 'Keperluan keluarga',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('leave_requests', [
        'user_id'       => $user->id,
        'leave_type_id' => $leaveType->id,
        'status'        => 'pending',
    ]);
});

it('returns 422 when required fields are missing', function () {
    $company = Company::factory()->create();
    $user    = User::factory()->for($company)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/leaves', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['leave_type_id', 'start_date', 'end_date']);
});

it('returns 422 when start_date is in the past', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $leaveType = LeaveType::factory()->for($company)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/leaves', [
        'leave_type_id' => $leaveType->id,
        'start_date'    => now()->subDay()->format('Y-m-d'),
        'end_date'      => now()->format('Y-m-d'),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date']);
});

it('returns 422 when leave type belongs to another company', function () {
    $company      = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user         = User::factory()->for($company)->create();
    $leaveType    = LeaveType::factory()->for($otherCompany)->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/leaves', [
        'leave_type_id' => $leaveType->id,
        'start_date'    => now()->nextWeekday()->format('Y-m-d'),
        'end_date'      => now()->nextWeekday()->format('Y-m-d'),
    ]);

    $response->assertNotFound();
});

it('returns 422 when balance is insufficient', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $leaveType = LeaveType::factory()->for($company)->create(['default_quota' => 12]);

    LeaveBalance::factory()->for($user)->for($leaveType)->withQuota(12, 11)->create([
        'year' => now()->year,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    // Request 5 working days but only 1 remaining
    $start = now()->nextWeekday();
    $end   = $start->copy()->addWeekdays(4);

    $response = $this->withToken($token)->postJson('/api/v1/leaves', [
        'leave_type_id' => $leaveType->id,
        'start_date'    => $start->format('Y-m-d'),
        'end_date'      => $end->format('Y-m-d'),
        'reason'        => 'Keperluan mendadak',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('returns 422 when attachment is required but not provided', function () {
    $company   = Company::factory()->create();
    $user      = User::factory()->for($company)->create();
    $leaveType = LeaveType::factory()->requiresAttachment()->for($company)->create([
        'default_quota' => 10,
    ]);

    LeaveBalance::factory()->for($user)->for($leaveType)->withQuota(10, 0)->create([
        'year' => now()->year,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/leaves', [
        'leave_type_id' => $leaveType->id,
        'start_date'    => now()->nextWeekday()->format('Y-m-d'),
        'end_date'      => now()->nextWeekday()->format('Y-m-d'),
        'reason'        => 'Sakit',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('returns 401 without token', function () {
    $this->postJson('/api/v1/leaves', [])->assertUnauthorized();
});
