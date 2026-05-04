<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalRequestType;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ApprovalRepositoryInterface
{
    public function getPendingLeaveRequests(User $approver, array $filters): LengthAwarePaginator;

    public function getPendingPermissionRequests(User $approver, array $filters): LengthAwarePaginator;

    public function createApproval(
        ApprovalRequestType $requestType,
        int $requestId,
        User $approver,
        ApprovalDecision $decision,
        ?string $comment
    ): Approval;

    /**
     * Paginated combined leave+permission requests for the admin approval center.
     * Returns a mixed paginator; each item has a `request_type` key.
     */
    public function paginateForAdmin(User $approver, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator;
}
