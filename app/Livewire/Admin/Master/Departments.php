<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Master;

use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Admin\AdminComponent;

#[Layout('layouts.admin')]
#[Title('Departemen')]
class Departments extends AdminComponent
{
    public ?int $editingId = null;

    public string $name      = '';
    public string $managerId = '';

    protected DepartmentRepositoryInterface $deptRepo;
    protected UserRepositoryInterface $userRepo;

    public function boot(
        DepartmentRepositoryInterface $deptRepo,
        UserRepositoryInterface $userRepo,
    ): void {
        $this->deptRepo = $deptRepo;
        $this->userRepo = $userRepo;
    }

    protected function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:150'],
            'managerId' => ['nullable', 'exists:users,id'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name'      => 'nama departemen',
            'managerId' => 'manager',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'managerId', 'editingId']);
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'dept-form');
    }

    public function openEdit(int $id): void
    {
        $dept = $this->deptRepo->findById($id);

        if (! $dept) {
            return;
        }

        $this->editingId = $id;
        $this->name      = $dept->name;
        $this->managerId = (string) ($dept->manager_id ?? '');
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'dept-form');
    }

    public function save(): void
    {
        $this->validate();

        $companyId = $this->authUser()->company_id;

        $data = [
            'company_id' => $companyId,
            'name'       => $this->name,
            'manager_id' => $this->managerId ?: null,
        ];

        if ($this->editingId) {
            $dept = $this->deptRepo->findById($this->editingId);
            $this->deptRepo->update($dept, $data);
            $this->dispatch('toast', type: 'success', message: 'Departemen berhasil diperbarui.');
        } else {
            $this->deptRepo->create($data);
            $this->dispatch('toast', type: 'success', message: 'Departemen berhasil ditambahkan.');
        }

        $this->dispatch('close-modal', name: 'dept-form');
        $this->reset(['name', 'managerId', 'editingId']);
    }

    public function delete(int $id): void
    {
        $dept = $this->deptRepo->findById($id);

        if (! $dept || $dept->company_id !== $this->authUser()->company_id) {
            return;
        }

        if ($dept->users_count > 0) {
            $this->dispatch('toast', type: 'error', message: 'Tidak dapat menghapus departemen yang masih memiliki karyawan.');
            return;
        }

        $this->deptRepo->delete($dept);
        $this->dispatch('toast', type: 'success', message: 'Departemen berhasil dihapus.');
    }

    public function getManagers(): Collection
    {
        return $this->userRepo->getManagers($this->authUser()->company_id);
    }

    public function render(): View
    {
        return view('livewire.admin.master.departments', [
            'departments' => $this->deptRepo->getByCompany($this->authUser()->company_id),
            'managers'    => $this->getManagers(),
        ]);
    }
}
