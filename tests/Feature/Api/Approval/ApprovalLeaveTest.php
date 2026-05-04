<?php

declare(strict_types=1);

use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Approve ──────────────────────────────────────────────────────────────────

it('hr can approve a pending leave request', function () {
    $company   = Company::factory()->create();
    $hr        = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee  = User::factory()->create(['company_id' => $company->id]);
    $leaveType = LeaveType::factory()->create(['company_id' => $company->id, 'default_quota' => 0]);
    $leave     = LeaveRequest::factory()->pending()->create([
        'user_id'       => $employee->id,
        'leave_type_id' => $leaveType->id,
        'total_days'    => 3,
    ]);

    $token = $hr->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/leaves/{$leave->id}/approve", ['comment' => 'OK'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Pengajuan cuti berhasil disetujui.');

    expect($leave->fresh()->status)->toBe(LeaveStatus::APPROVED);
});

it('approve creates an approval record', function () {
    $company   = Company::factory()->create();
    $hr        = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee  = User::factory()->create(['company_id' => $company->id]);
    $leaveType = LeaveType::factory()->create(['company_id' => $company->id, 'default_quota' => 0]);
    $leave     = LeaveRequest::factory()->pending()->create([
        'user_id'       => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $token = $hr->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/leaves/{$leave->id}/approve", ['comment' => 'Approved']);

    $this->assertDatabaseHas('approvals', [
        'request_id'   => $leave->id,
        'request_type' => 'leave',
        'approver_id'  => $hr->id,
        'decision'     => 'approved',
        'comment'      => 'Approved',
    ]);
});

it('approve deducts leave balance for quota-based leave type', function () {
    $company   = Company::factory()->create();
    $hr        = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee  = User::factory()->create(['company_id' => $company->id]);
    $leaveType = LeaveType::factory()->create(['company_id' => $company->id, 'default_quota' => 12]);
    $leave     = LeaveRequest::factory()->pending()->create([
        'user_id'       => $employee->id,
        'leave_type_id' => $leaveType->id,
        'total_days'    => 3,
        'start_date'    => now()->toDateString(),
    ]);

    LeaveBalance::factory()->create([
        'user_id'       => $employee->id,
        'leave_type_id' => $leaveType->id,
        'year'          => now()->year,
        'total_quota'   => 12,
        'used'          => 0,
        'remaining'     => 12,
    ]);

    $token = $hr->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/leaves/{$leave->id}/approve");

    $balance = LeaveBalance::where('user_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->first();

    expect($balance->used)->toBe(3)
        ->and($balance->remaining)->toBe(9);
});

it('returns 422 when trying to approve an already-processed leave', function () {
    $company   = Company::factory()->create();
    $hr        = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee  = User::factory()->create(['company_id' => $company->id]);
    $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
    $leave     = LeaveRequest::factory()->approved()->create([
        'user_id'       => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $token = $hr->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/leaves/{$leave->id}/approve")
        ->assertStatus(422);
});

it('manager cannot approve leave outside their department', function () {
    $company  = Company::factory()->create();
    $manager  = User::factory()->manager()->create(['company_id' => $company->id]);
    $ownDept  = Department::factory()->create(['company_id' => $company->id, 'manager_id' => $manager->id]);
    $otherDept = Department::factory()->create(['company_id' => $company->id, 'manager_id' => null]);

    $employee  = User::factory()->create(['company_id' => $company->id, 'department_id' => $otherDept->id]);
    $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
    $leave     = LeaveRequest::factory()->pending()->create([
        'user_id'       => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $token = $manager->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/leaves/{$leave->id}/approve")
        ->assertStatus(403);
});

it('employee cannot approve a leave request', function () {
    $company   = Company::factory()->create();
    $employee  = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::EMPLOYEE]);
    $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
    $leave     = LeaveRequest::factory()->pending()->create([
        'user_id'       => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $token = $employee->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/leaves/{$leave->id}/approve")
        ->assertStatus(403);
});

it('returns 404 for non-existent leave on approve', function () {
    $company = Company::factory()->create();
    $hr      = User::factory()->hr()->create(['company_id' => $company->id]);
    $token   = $hr->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/leaves/99999/approve')
        ->assertStatus(404);
});

// ─── Reject ───────────────────────────────────────────────────────────────────

it('hr can reject a pending leave request', function () {
    $company   = Company::factory()->create();
    $hr        = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee  = User::factory()->create(['company_id' => $company->id]);
    $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
    $leave     = LeaveRequest::factory()->pending()->create([
        'user_id'       => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $token = $hr->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/leaves/{$leave->id}/reject", ['comment' => 'Tidak memenuhi syarat'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Pengajuan cuti berhasil ditolak.');

    expect($leave->fresh()->status)->toBe(LeaveStatus::REJECTED);

    $this->assertDatabaseHas('approvals', [
        'request_id' => $leave->id,
        'decision'   => 'rejected',
        'comment'    => 'Tidak memenuhi syarat',
    ]);
});

it('returns 422 when trying to reject an already-processed leave', function () {
    $company   = Company::factory()->create();
    $hr        = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee  = User::factory()->create(['company_id' => $company->id]);
    $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
    $leave     = LeaveRequest::factory()->rejected()->create([
        'user_id'       => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $token = $hr->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/leaves/{$leave->id}/reject")
        ->assertStatus(422);
});
