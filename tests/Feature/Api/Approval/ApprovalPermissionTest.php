<?php

declare(strict_types=1);

use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Department;
use App\Models\PermissionRequest;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Approve ──────────────────────────────────────────────────────────────────

it('hr can approve a pending permission request', function () {
    $company    = Company::factory()->create();
    $hr         = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee   = User::factory()->create(['company_id' => $company->id]);
    $permission = PermissionRequest::factory()->pending()->create(['user_id' => $employee->id]);

    $token = $hr->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/permissions/{$permission->id}/approve", ['comment' => 'Silakan'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Pengajuan izin berhasil disetujui.');

    expect($permission->fresh()->status)->toBe(LeaveStatus::APPROVED);
});

it('approve permission creates an approval record', function () {
    $company    = Company::factory()->create();
    $hr         = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee   = User::factory()->create(['company_id' => $company->id]);
    $permission = PermissionRequest::factory()->pending()->create(['user_id' => $employee->id]);

    $token = $hr->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/permissions/{$permission->id}/approve", ['comment' => 'OK']);

    $this->assertDatabaseHas('approvals', [
        'request_id'   => $permission->id,
        'request_type' => 'permission',
        'approver_id'  => $hr->id,
        'decision'     => 'approved',
        'comment'      => 'OK',
    ]);
});

it('returns 422 when trying to approve an already-processed permission', function () {
    $company    = Company::factory()->create();
    $hr         = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee   = User::factory()->create(['company_id' => $company->id]);
    $permission = PermissionRequest::factory()->approved()->create(['user_id' => $employee->id]);

    $token = $hr->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/permissions/{$permission->id}/approve")
        ->assertStatus(422);
});

it('manager cannot approve permission outside their department', function () {
    $company   = Company::factory()->create();
    $manager   = User::factory()->manager()->create(['company_id' => $company->id]);
    $ownDept   = Department::factory()->create(['company_id' => $company->id, 'manager_id' => $manager->id]);
    $otherDept = Department::factory()->create(['company_id' => $company->id, 'manager_id' => null]);

    $employee   = User::factory()->create(['company_id' => $company->id, 'department_id' => $otherDept->id]);
    $permission = PermissionRequest::factory()->pending()->create(['user_id' => $employee->id]);

    $token = $manager->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/permissions/{$permission->id}/approve")
        ->assertStatus(403);
});

it('employee cannot approve a permission request', function () {
    $company    = Company::factory()->create();
    $employee   = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::EMPLOYEE]);
    $permission = PermissionRequest::factory()->pending()->create(['user_id' => $employee->id]);

    $token = $employee->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/permissions/{$permission->id}/approve")
        ->assertStatus(403);
});

it('returns 404 for non-existent permission on approve', function () {
    $company = Company::factory()->create();
    $hr      = User::factory()->hr()->create(['company_id' => $company->id]);
    $token   = $hr->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/permissions/99999/approve')
        ->assertStatus(404);
});

// ─── Reject ───────────────────────────────────────────────────────────────────

it('hr can reject a pending permission request', function () {
    $company    = Company::factory()->create();
    $hr         = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee   = User::factory()->create(['company_id' => $company->id]);
    $permission = PermissionRequest::factory()->pending()->create(['user_id' => $employee->id]);

    $token = $hr->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/permissions/{$permission->id}/reject", ['comment' => 'Tidak disetujui'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Pengajuan izin berhasil ditolak.');

    expect($permission->fresh()->status)->toBe(LeaveStatus::REJECTED);

    $this->assertDatabaseHas('approvals', [
        'request_id' => $permission->id,
        'decision'   => 'rejected',
        'comment'    => 'Tidak disetujui',
    ]);
});

it('manager can reject permission from their own department', function () {
    $company    = Company::factory()->create();
    $manager    = User::factory()->manager()->create(['company_id' => $company->id]);
    $dept       = Department::factory()->create(['company_id' => $company->id, 'manager_id' => $manager->id]);
    $employee   = User::factory()->create(['company_id' => $company->id, 'department_id' => $dept->id]);
    $permission = PermissionRequest::factory()->pending()->create(['user_id' => $employee->id]);

    $token = $manager->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/permissions/{$permission->id}/reject")
        ->assertOk();

    expect($permission->fresh()->status)->toBe(LeaveStatus::REJECTED);
});

it('returns 422 when trying to reject an already-processed permission', function () {
    $company    = Company::factory()->create();
    $hr         = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee   = User::factory()->create(['company_id' => $company->id]);
    $permission = PermissionRequest::factory()->rejected()->create(['user_id' => $employee->id]);

    $token = $hr->createToken('test')->plainTextToken;
    $this->withToken($token)
        ->postJson("/api/v1/permissions/{$permission->id}/reject")
        ->assertStatus(422);
});
