<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AuditLogRepositoryInterface
{
    /** Return the most recent $limit audit log entries for a company. */
    public function getRecent(int $companyId, int $limit = 10): Collection;

    /** Return paginated audit logs for admin viewer with optional filters. */
    public function paginateForAdmin(int $companyId, array $filters = []): LengthAwarePaginator;

    public function log(
        ?int $userId,
        string $action,
        ?string $entity = null,
        ?string $entityId = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
    ): void;
}
