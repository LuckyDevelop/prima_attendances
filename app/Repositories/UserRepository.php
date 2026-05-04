<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Device;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::with(['company', 'department', 'officeLocation'])->find($id);
    }

    public function findOrFail(int $id): User
    {
        return User::with(['company', 'department', 'officeLocation'])->findOrFail($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::with(['company', 'department', 'officeLocation'])
            ->where('email', $email)
            ->first();
    }

    public function paginate(int $companyId, array $filters = []): LengthAwarePaginator
    {
        $query = User::with(['department:id,name', 'officeLocation:id,name'])
            ->where('company_id', $companyId)
            ->select(['id', 'company_id', 'department_id', 'office_location_id',
                       'employee_id', 'full_name', 'email', 'phone', 'photo', 'role', 'status']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('full_name')->paginate($filters['per_page'] ?? 15);
    }

    public function getManagers(int $companyId): Collection
    {
        return User::where('company_id', $companyId)
            ->whereIn('role', [UserRole::MANAGER, UserRole::HR, UserRole::ADMIN])
            ->where('status', UserStatus::ACTIVE)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_id']);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->load(['company', 'department', 'officeLocation']);
    }

    public function updatePassword(User $user, string $hashedPassword): User
    {
        $user->update(['password' => $hashedPassword]);

        return $user;
    }

    public function updatePhoto(User $user, ?string $path): User
    {
        $user->update(['photo' => $path]);

        return $user;
    }

    public function updateFaceEmbedding(User $user, string $embedding): User
    {
        $user->update(['face_embedding' => $embedding]);

        return $user;
    }

    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }

    public function countActive(int $companyId): int
    {
        return User::where('company_id', $companyId)
            ->where('status', UserStatus::ACTIVE)
            ->count();
    }

    public function getAllActive(int $companyId): Collection
    {
        return User::where('company_id', $companyId)
            ->where('status', UserStatus::ACTIVE)
            ->orderBy('full_name')
            ->get();
    }

    public function updateRole(User $user, UserRole $role): User
    {
        $user->update(['role' => $role]);

        return $user->fresh();
    }

    public function updateStatus(User $user, UserStatus $status): User
    {
        $user->update(['status' => $status]);

        return $user->fresh();
    }

    public function updateOrCreateDevice(User $user, array $data): Device
    {
        return Device::updateOrCreate(
            [
                'user_id'   => $user->id,
                'device_id' => $data['device_id'],
            ],
            [
                'device_name' => $data['device_name'],
                'platform'    => $data['platform'],
                'fcm_token'   => $data['fcm_token'] ?? null,
                'is_active'   => true,
                'last_login'  => now(),
            ],
        );
    }
}
