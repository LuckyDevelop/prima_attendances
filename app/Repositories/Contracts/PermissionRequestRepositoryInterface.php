<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\LeaveStatus;
use App\Models\PermissionRequest;
use Illuminate\Pagination\LengthAwarePaginator;

interface PermissionRequestRepositoryInterface
{
    public function paginateByUser(int $userId, array $filters = []): LengthAwarePaginator;

    public function find(int $id): ?PermissionRequest;

    /** Find a permission request with user relation loaded, for approval actions. */
    public function findForApproval(int $id): ?PermissionRequest;

    public function create(int $userId, array $data): PermissionRequest;

    public function updateStatus(PermissionRequest $permission, LeaveStatus $status): PermissionRequest;

    public function cancel(PermissionRequest $permission): bool;

    public function existsForDateAndType(int $userId, string $date, string $type): bool;

    /** Paginated list for admin panel with advanced filters. */
    public function paginateForAdmin(int $companyId, array $filters = []): LengthAwarePaginator;
}
