<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    public function getByCompany(int $companyId): Collection;

    public function findById(int $id): ?Department;

    public function create(array $data): Department;

    public function update(Department $department, array $data): Department;

    public function delete(Department $department): bool;
}
