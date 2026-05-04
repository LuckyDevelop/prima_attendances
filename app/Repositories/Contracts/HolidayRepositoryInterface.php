<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Holiday;
use Illuminate\Database\Eloquent\Collection;

interface HolidayRepositoryInterface
{
    public function getByCompanyAndYear(int $companyId, int $year, ?int $month = null): Collection;

    public function findById(int $id): ?Holiday;

    public function create(array $data): Holiday;

    public function update(Holiday $holiday, array $data): Holiday;

    public function delete(Holiday $holiday): bool;
}
