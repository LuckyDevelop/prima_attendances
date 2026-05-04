<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function find(int $id): ?Company
    {
        return Company::find($id);
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->fresh();
    }

    public function updateLogo(Company $company, ?string $path): Company
    {
        $company->update(['logo' => $path]);

        return $company->fresh();
    }
}
