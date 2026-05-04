<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Holiday;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class HolidayRepository implements HolidayRepositoryInterface
{
    public function getByCompanyAndYear(int $companyId, int $year, ?int $month = null): Collection
    {
        $query = Holiday::where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhere('is_national', true);
            })
            ->whereYear('date', $year)
            ->orderBy('date');

        if ($month !== null) {
            $query->whereMonth('date', $month);
        }

        return $query->get();
    }

    public function findById(int $id): ?Holiday
    {
        return Holiday::find($id);
    }

    public function create(array $data): Holiday
    {
        return Holiday::create($data);
    }

    public function update(Holiday $holiday, array $data): Holiday
    {
        $holiday->update($data);

        return $holiday->fresh();
    }

    public function delete(Holiday $holiday): bool
    {
        return $holiday->delete();
    }
}
