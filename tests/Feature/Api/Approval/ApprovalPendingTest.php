<?php

declare(strict_types=1);

use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PermissionRequest;
use App\Models\User;

use function Pest\Laravel\getJson;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns 401 for unauthenticated request', function () {
    getJson('/api/v1/approvals/pending')
        ->assertStatus(401);
});

it('returns 403 for employee role', function () {
    $company  = Company::factory()->create();
    $employee = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::EMPLOYEE]);
    $token    = $employee->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/approvals/pending')
        ->assertStatus(403);
});

it('returns pending requests for hr', function () {
    $company  = Company::factory()->create();
    $hr       = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::EMPLOYEE]);

    $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
    LeaveRequest::factory()->pending()->create(['user_id' => $employee->id, 'leave_type_id' => $leaveType->id]);
    PermissionRequest::factory()->pending()->create(['user_id' => $employee->id]);

    $token = $hr->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/approvals/pending')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['leaves', 'permissions'],
            'meta' => [
                'leaves'      => ['current_page', 'last_page', 'per_page', 'total'],
                'permissions' => ['current_page', 'last_page', 'per_page', 'total'],
            ],
        ]);

    expect($response->json('data.leaves'))->toHaveCount(1)
        ->and($response->json('data.permissions'))->toHaveCount(1);
});

it('manager only sees pending requests from their departments', function () {
    $company = Company::factory()->create();
    $manager = User::factory()->manager()->create(['company_id' => $company->id]);

    $ownDept  = Department::factory()->create(['company_id' => $company->id, 'manager_id' => $manager->id]);
    $otherDept = Department::factory()->create(['company_id' => $company->id, 'manager_id' => null]);

    $ownEmployee   = User::factory()->create(['company_id' => $company->id, 'department_id' => $ownDept->id]);
    $otherEmployee = User::factory()->create(['company_id' => $company->id, 'department_id' => $otherDept->id]);

    $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);
    LeaveRequest::factory()->pending()->create(['user_id' => $ownEmployee->id, 'leave_type_id' => $leaveType->id]);
    LeaveRequest::factory()->pending()->create(['user_id' => $otherEmployee->id, 'leave_type_id' => $leaveType->id]);

    $token    = $manager->createToken('test')->plainTextToken;
    $response = $this->withToken($token)
        ->getJson('/api/v1/approvals/pending')
        ->assertOk();

    // manager should only see the request from their department
    expect($response->json('data.leaves'))->toHaveCount(1)
        ->and($response->json('data.leaves.0.id'))->toBe(
            LeaveRequest::where('user_id', $ownEmployee->id)->first()->id
        );
});

it('does not return already-processed requests', function () {
    $company  = Company::factory()->create();
    $hr       = User::factory()->hr()->create(['company_id' => $company->id]);
    $employee = User::factory()->create(['company_id' => $company->id]);
    $leaveType = LeaveType::factory()->create(['company_id' => $company->id]);

    LeaveRequest::factory()->approved()->create(['user_id' => $employee->id, 'leave_type_id' => $leaveType->id]);
    LeaveRequest::factory()->rejected()->create(['user_id' => $employee->id, 'leave_type_id' => $leaveType->id]);

    $token    = $hr->createToken('test')->plainTextToken;
    $response = $this->withToken($token)
        ->getJson('/api/v1/approvals/pending')
        ->assertOk();

    expect($response->json('data.leaves'))->toHaveCount(0);
});
