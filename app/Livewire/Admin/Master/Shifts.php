<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Master;

use App\Repositories\Contracts\ShiftRepositoryInterface;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Admin\AdminComponent;

#[Layout('layouts.admin')]
#[Title('Shift Kerja')]
class Shifts extends AdminComponent
{
    public ?int $editingId = null;

    public string $name         = '';
    public string $startTime    = '08:00';
    public string $endTime      = '17:00';
    public int    $breakMinutes = 60;

    protected ShiftRepositoryInterface $shiftRepo;

    public function boot(ShiftRepositoryInterface $shiftRepo): void
    {
        $this->shiftRepo = $shiftRepo;
    }

    protected function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:150'],
            'startTime'    => ['required', 'date_format:H:i'],
            'endTime'      => ['required', 'date_format:H:i'],
            'breakMinutes' => ['required', 'integer', 'min:0', 'max:480'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name'         => 'nama shift',
            'startTime'    => 'jam mulai',
            'endTime'      => 'jam selesai',
            'breakMinutes' => 'istirahat',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'editingId']);
        $this->startTime    = '08:00';
        $this->endTime      = '17:00';
        $this->breakMinutes = 60;
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'shift-form');
    }

    public function openEdit(int $id): void
    {
        $shift = $this->shiftRepo->findShiftById($id);

        if (! $shift) {
            return;
        }

        $this->editingId    = $id;
        $this->name         = $shift->name;
        $this->startTime    = substr((string) $shift->start_time, 0, 5);
        $this->endTime      = substr((string) $shift->end_time, 0, 5);
        $this->breakMinutes = $shift->break_minutes;
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'shift-form');
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'company_id'    => $this->authUser()->company_id,
            'name'          => $this->name,
            'start_time'    => $this->startTime,
            'end_time'      => $this->endTime,
            'break_minutes' => $this->breakMinutes,
        ];

        if ($this->editingId) {
            $shift = $this->shiftRepo->findShiftById($this->editingId);
            $this->shiftRepo->updateShift($shift, $data);
            $this->dispatch('toast', type: 'success', message: 'Shift berhasil diperbarui.');
        } else {
            $this->shiftRepo->createShift($data);
            $this->dispatch('toast', type: 'success', message: 'Shift berhasil ditambahkan.');
        }

        $this->dispatch('close-modal', name: 'shift-form');
        $this->reset(['name', 'editingId']);
    }

    public function delete(int $id): void
    {
        $shift = $this->shiftRepo->findShiftById($id);

        if (! $shift || $shift->company_id !== $this->authUser()->company_id) {
            return;
        }

        $this->shiftRepo->deleteShift($shift);
        $this->dispatch('toast', type: 'success', message: 'Shift berhasil dihapus.');
    }

    /** Net work minutes excluding break. */
    public function getWorkMinutes(): int
    {
        if (! $this->startTime || ! $this->endTime) {
            return 0;
        }

        [$sh, $sm] = array_map('intval', explode(':', $this->startTime));
        [$eh, $em] = array_map('intval', explode(':', $this->endTime));

        $total = ($eh * 60 + $em) - ($sh * 60 + $sm);

        return max(0, $total - $this->breakMinutes);
    }

    public function render(): View
    {
        return view('livewire.admin.master.shifts', [
            'shifts'      => $this->shiftRepo->getAllByCompany($this->authUser()->company_id),
            'workMinutes' => $this->getWorkMinutes(),
        ]);
    }
}
