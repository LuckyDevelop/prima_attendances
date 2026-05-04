<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Company;

interface CompanyRepositoryInterface
{
    public function find(int $id): ?Company;

    public function update(Company $company, array $data): Company;

    public function updateLogo(Company $company, ?string $path): Company;
}
