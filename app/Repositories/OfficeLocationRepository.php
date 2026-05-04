<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\OfficeLocation;
use App\Repositories\Contracts\OfficeLocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OfficeLocationRepository implements OfficeLocationRepositoryInterface
{
    public function getByCompany(int $companyId): Collection
    {
        return OfficeLocation::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getAllByCompany(int $companyId): Collection
    {
        return OfficeLocation::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
    }

    public function getById(int $id): mixed
    {
        return OfficeLocation::find($id);
    }

    public function create(array $data): OfficeLocation
    {
        return OfficeLocation::create($data);
    }

    public function update(OfficeLocation $location, array $data): OfficeLocation
    {
        $location->update($data);

        return $location->fresh();
    }

    public function delete(OfficeLocation $location): bool
    {
        return $location->delete();
    }
}
