<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LeaveType;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LeaveTypeRepository implements LeaveTypeRepositoryInterface
{
    public function getByCompany(int $companyId): Collection
    {
        return LeaveType::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?LeaveType
    {
        return LeaveType::find($id);
    }

    public function create(array $data): LeaveType
    {
        return LeaveType::create($data);
    }

    public function update(LeaveType $leaveType, array $data): LeaveType
    {
        $leaveType->update($data);

        return $leaveType->fresh();
    }

    public function delete(LeaveType $leaveType): bool
    {
        return $leaveType->delete();
    }
}
