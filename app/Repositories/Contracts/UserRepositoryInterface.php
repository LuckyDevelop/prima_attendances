<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findOrFail(int $id): User;

    public function findByEmail(string $email): ?User;

    public function paginate(int $companyId, array $filters = []): LengthAwarePaginator;

    public function getManagers(int $companyId): Collection;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function updatePassword(User $user, string $hashedPassword): User;

    public function updatePhoto(User $user, ?string $path): User;

    public function updateFaceEmbedding(User $user, string $embedding): User;

    public function delete(User $user): bool;

    public function updateOrCreateDevice(User $user, array $data): Device;

    public function countActive(int $companyId): int;

    public function getAllActive(int $companyId): Collection;

    public function updateRole(User $user, UserRole $role): User;

    public function updateStatus(User $user, UserStatus $status): User;
}
