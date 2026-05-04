<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Employee;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\OfficeLocationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use App\Livewire\Admin\AdminComponent;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class Form extends AdminComponent
{
    use WithFileUploads;

    public ?int $userId = null;

    #[Validate]
    public string $fullName    = '';
    #[Validate]
    public string $employeeId  = '';
    #[Validate]
    public string $email       = '';
    public string $phone       = '';
    public string $departmentId   = '';
    public string $officeLocationId = '';
    public string $role        = '';
    public string $status      = 'active';
    public string $password    = '';
    public string $password_confirmation = '';
    public mixed  $photo       = null;
    public ?string $existingPhoto = null;

    protected UserRepositoryInterface $userRepo;
    protected DepartmentRepositoryInterface $deptRepo;
    protected OfficeLocationRepositoryInterface $locationRepo;

    public function boot(
        UserRepositoryInterface $userRepo,
        DepartmentRepositoryInterface $deptRepo,
        OfficeLocationRepositoryInterface $locationRepo,
    ): void {
        $this->userRepo     = $userRepo;
        $this->deptRepo     = $deptRepo;
        $this->locationRepo = $locationRepo;
    }

    public function mount(?int $userId = null): void
    {
        $this->userId = $userId;

        if ($userId) {
            $user = $this->userRepo->findOrFail($userId);

            $this->fillFromUser($user);
        }
    }

    /** @param \App\Models\User $user */
    private function fillFromUser(mixed $user): void
    {
        $this->fullName           = $user->full_name;
        $this->employeeId         = $user->employee_id;
        $this->email              = $user->email;
        $this->phone              = $user->phone ?? '';
        $this->departmentId       = (string) ($user->department_id ?? '');
        $this->officeLocationId   = (string) ($user->office_location_id ?? '');
        $this->role               = $user->role->value;
        $this->status             = $user->status->value;
        $this->existingPhoto      = $user->photo;
    }

    protected function rules(): array
    {
        $uniqueEmail = 'unique:users,email' . ($this->userId ? ",{$this->userId}" : '');
        $uniqueEmpId = 'unique:users,employee_id' . ($this->userId ? ",{$this->userId}" : '');

        return [
            'fullName'            => ['required', 'string', 'max:150'],
            'employeeId'          => ['required', 'string', 'max:50', $uniqueEmpId],
            'email'               => ['required', 'email', 'max:255', $uniqueEmail],
            'phone'               => ['nullable', 'string', 'max:20'],
            'departmentId'        => ['nullable', 'exists:departments,id'],
            'officeLocationId'    => ['nullable', 'exists:office_locations,id'],
            'role'                => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
            'status'              => ['required', 'in:' . implode(',', array_column(UserStatus::cases(), 'value'))],
            'password'            => $this->userId
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
            'photo'               => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'fullName'            => 'nama lengkap',
            'employeeId'          => 'NIK',
            'email'               => 'email',
            'phone'               => 'telepon',
            'departmentId'        => 'departemen',
            'officeLocationId'    => 'lokasi kantor',
            'role'                => 'role',
            'status'              => 'status',
            'password'            => 'password',
            'photo'               => 'foto',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $companyId = $this->authUser()->company_id;

        $data = [
            'company_id'         => $companyId,
            'full_name'          => $this->fullName,
            'employee_id'        => $this->employeeId,
            'email'              => $this->email,
            'phone'              => $this->phone ?: null,
            'department_id'      => $this->departmentId ?: null,
            'office_location_id' => $this->officeLocationId ?: null,
            'role'               => $this->role,
            'status'             => $this->status,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->photo) {
            $user  = $this->userId ? $this->userRepo->findOrFail($this->userId) : null;
            $path  = $this->photo->store("photos/{$companyId}", 'public');

            if ($user && $user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $data['photo'] = $path;
        }

        if ($this->userId) {
            $user = $this->userRepo->findOrFail($this->userId);
            $this->userRepo->update($user, $data);
            $this->dispatch('toast', type: 'success', message: 'Data karyawan berhasil diperbarui.');
        } else {
            $this->userRepo->create($data);
            $this->dispatch('toast', type: 'success', message: 'Karyawan berhasil ditambahkan.');
        }

        $this->redirectRoute('admin.employees.index', navigate: true);
    }

    public function getDepartments(): Collection
    {
        return $this->deptRepo->getByCompany($this->authUser()->company_id);
    }

    public function getLocations(): Collection
    {
        return $this->locationRepo->getByCompany($this->authUser()->company_id);
    }

    public function render(): View
    {
        $title = $this->userId ? 'Edit Karyawan' : 'Tambah Karyawan';

        return view('livewire.admin.employee.form', [
            'departments' => $this->getDepartments(),
            'locations'   => $this->getLocations(),
            'roles'       => UserRole::cases(),
            'statuses'    => UserStatus::cases(),
            'pageTitle'   => $title,
        ])->title($title);
    }
}
