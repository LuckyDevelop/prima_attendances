<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Leave;

use App\Livewire\Admin\AdminComponent;
use App\Models\LeaveBalance;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Saldo Cuti')]
class Balances extends AdminComponent
{
    use WithPagination;

    #[Url]
    public int $year;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $departmentId = '';

    public ?int $editingBalanceId = null;
    public int $editQuota = 0;

    protected LeaveBalanceRepositoryInterface $balanceRepo;
    protected LeaveTypeRepositoryInterface $leaveTypeRepo;
    protected DepartmentRepositoryInterface $deptRepo;

    public function boot(
        LeaveBalanceRepositoryInterface $balanceRepo,
        LeaveTypeRepositoryInterface $leaveTypeRepo,
        DepartmentRepositoryInterface $deptRepo,
    ): void {
        $this->balanceRepo   = $balanceRepo;
        $this->leaveTypeRepo = $leaveTypeRepo;
        $this->deptRepo      = $deptRepo;
    }

    public function mount(): void
    {
        $this->year = (int) now()->format('Y');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedDepartmentId(): void { $this->resetPage(); }

    public function openEdit(int $balanceId): void
    {
        $balance = LeaveBalance::find($balanceId);

        if (! $balance) {
            return;
        }

        $this->editingBalanceId = $balanceId;
        $this->editQuota        = $balance->total_quota;
        $this->dispatch('open-modal', name: 'balance-edit');
    }

    public function saveBalance(): void
    {
        $this->validate([
            'editQuota' => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        $balance = LeaveBalance::find($this->editingBalanceId);

        if (! $balance) {
            $this->dispatch('close-modal', name: 'balance-edit');
            return;
        }

        $this->balanceRepo->adjustBalance($balance, $this->editQuota);

        $this->dispatch('toast', type: 'success', message: 'Saldo cuti berhasil diperbarui.');
        $this->dispatch('close-modal', name: 'balance-edit');
        $this->editingBalanceId = null;
    }

    public function getEmployees(): LengthAwarePaginator
    {
        return $this->balanceRepo->paginateByCompany(
            $this->authUser()->company_id,
            $this->year,
            [
                'search'        => $this->search ?: null,
                'department_id' => $this->departmentId ?: null,
            ]
        );
    }

    public function getLeaveTypes(): Collection
    {
        return $this->leaveTypeRepo->getByCompany($this->authUser()->company_id);
    }

    public function getDepartments(): Collection
    {
        return $this->deptRepo->getByCompany($this->authUser()->company_id);
    }

    public function render(): View
    {
        return view('livewire.admin.leave.balances', [
            'employees'  => $this->getEmployees(),
            'leaveTypes' => $this->getLeaveTypes(),
            'departments'=> $this->getDepartments(),
        ]);
    }
}
