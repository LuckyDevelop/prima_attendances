<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Collection;

interface LeaveTypeRepositoryInterface
{
    public function getByCompany(int $companyId): Collection;

    public function find(int $id): ?LeaveType;

    public function create(array $data): LeaveType;

    public function update(LeaveType $leaveType, array $data): LeaveType;

    public function delete(LeaveType $leaveType): bool;
}
