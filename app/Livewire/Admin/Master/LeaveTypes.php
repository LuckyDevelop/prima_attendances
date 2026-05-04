<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Master;

use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Admin\AdminComponent;

#[Layout('layouts.admin')]
#[Title('Jenis Cuti')]
class LeaveTypes extends AdminComponent
{
    public ?int $editingId = null;

    public string $name               = '';
    public int    $defaultQuota       = 0;
    public bool   $requiresAttachment = false;
    public bool   $isPaid             = true;

    protected LeaveTypeRepositoryInterface $leaveTypeRepo;

    public function boot(LeaveTypeRepositoryInterface $leaveTypeRepo): void
    {
        $this->leaveTypeRepo = $leaveTypeRepo;
    }

    protected function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:150'],
            'defaultQuota'       => ['required', 'integer', 'min:0', 'max:365'],
            'requiresAttachment' => ['boolean'],
            'isPaid'             => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name'         => 'nama jenis cuti',
            'defaultQuota' => 'kuota default',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'editingId']);
        $this->defaultQuota       = 0;
        $this->requiresAttachment = false;
        $this->isPaid             = true;
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'leave-type-form');
    }

    public function openEdit(int $id): void
    {
        $leaveType = $this->leaveTypeRepo->find($id);

        if (! $leaveType) {
            return;
        }

        $this->editingId          = $id;
        $this->name               = $leaveType->name;
        $this->defaultQuota       = $leaveType->default_quota;
        $this->requiresAttachment = (bool) $leaveType->requires_attachment;
        $this->isPaid             = (bool) $leaveType->is_paid;
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'leave-type-form');
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'company_id'          => $this->authUser()->company_id,
            'name'                => $this->name,
            'default_quota'       => $this->defaultQuota,
            'requires_attachment' => $this->requiresAttachment,
            'is_paid'             => $this->isPaid,
        ];

        if ($this->editingId) {
            $leaveType = $this->leaveTypeRepo->find($this->editingId);
            $this->leaveTypeRepo->update($leaveType, $data);
            $this->dispatch('toast', type: 'success', message: 'Jenis cuti berhasil diperbarui.');
        } else {
            $this->leaveTypeRepo->create($data);
            $this->dispatch('toast', type: 'success', message: 'Jenis cuti berhasil ditambahkan.');
        }

        $this->dispatch('close-modal', name: 'leave-type-form');
        $this->reset(['name', 'editingId']);
    }

    public function delete(int $id): void
    {
        $leaveType = $this->leaveTypeRepo->find($id);

        if (! $leaveType || $leaveType->company_id !== $this->authUser()->company_id) {
            return;
        }

        $this->leaveTypeRepo->delete($leaveType);
        $this->dispatch('toast', type: 'success', message: 'Jenis cuti berhasil dihapus.');
    }

    public function render(): View
    {
        return view('livewire.admin.master.leave-types', [
            'leaveTypes' => $this->leaveTypeRepo->getByCompany($this->authUser()->company_id),
        ]);
    }
}
