<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Approval;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LeaveRequestResource;
use App\Http\Resources\Api\PermissionRequestResource;
use App\Repositories\Contracts\ApprovalRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ApprovalRepositoryInterface $approvalRepository,
    ) {}

    /**
     * List all pending leave and permission requests visible to the approver.
     */
    public function pending(Request $request): JsonResponse
    {
        $filters = ['per_page' => $request->query('per_page', 15)];

        $leaves      = $this->approvalRepository->getPendingLeaveRequests($request->user(), $filters);
        $permissions = $this->approvalRepository->getPendingPermissionRequests($request->user(), $filters);

        return response()->json([
            'success' => true,
            'message' => 'Data pengajuan pending berhasil diambil.',
            'data'    => [
                'leaves'      => LeaveRequestResource::collection($leaves->items()),
                'permissions' => PermissionRequestResource::collection($permissions->items()),
            ],
            'meta'    => [
                'leaves' => [
                    'current_page' => $leaves->currentPage(),
                    'last_page'    => $leaves->lastPage(),
                    'per_page'     => $leaves->perPage(),
                    'total'        => $leaves->total(),
                ],
                'permissions' => [
                    'current_page' => $permissions->currentPage(),
                    'last_page'    => $permissions->lastPage(),
                    'per_page'     => $permissions->perPage(),
                    'total'        => $permissions->total(),
                ],
            ],
        ]);
    }
}
