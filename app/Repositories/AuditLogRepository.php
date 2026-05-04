<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function getRecent(int $companyId, int $limit = 10): Collection
    {
        return AuditLog::query()
            ->where(function ($q) use ($companyId) {
                $q->whereHas('user', fn ($u) => $u->where('company_id', $companyId))
                  ->orWhereNull('user_id');
            })
            ->with('user:id,full_name,photo')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function paginateForAdmin(int $companyId, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::with('user:id,full_name,photo')
            ->where(function ($q) use ($companyId) {
                $q->whereHas('user', fn ($u) => $u->where('company_id', $companyId))
                  ->orWhereNull('user_id');
            })
            ->orderByDesc('created_at');

        if (! empty($filters['entity'])) {
            $query->where('entity', $filters['entity']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', 'like', '%' . $filters['action'] . '%');
        }

        if (! empty($filters['user_search'])) {
            $search = $filters['user_search'];
            $query->whereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$search}%")
                ->orWhere('employee_id', 'like', "%{$search}%")
            );
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);

        return $query->paginate($perPage);
    }

    public function log(
        ?int $userId,
        string $action,
        ?string $entity = null,
        ?string $entityId = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
    ): void {
        AuditLog::create([
            'user_id'    => $userId,
            'action'     => $action,
            'entity'     => $entity,
            'entity_id'  => $entityId,
            'metadata'   => $metadata,
            'ip_address' => $ipAddress,
        ]);
    }
}
