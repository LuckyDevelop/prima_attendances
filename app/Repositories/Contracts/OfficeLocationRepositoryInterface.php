<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\OfficeLocation;
use Illuminate\Database\Eloquent\Collection;

interface OfficeLocationRepositoryInterface
{
    public function getByCompany(int $companyId): Collection;

    public function getAllByCompany(int $companyId): Collection;

    public function getById(int $id): mixed;

    public function create(array $data): OfficeLocation;

    public function update(OfficeLocation $location, array $data): OfficeLocation;

    public function delete(OfficeLocation $location): bool;
}
