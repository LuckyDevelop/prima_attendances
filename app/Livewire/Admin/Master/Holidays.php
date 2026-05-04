<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Master;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Hari Libur')]
class Holidays extends AdminComponent
{
    public ?int $editingId = null;

    public int    $year       = 0;
    public string $date       = '';
    public string $name       = '';
    public bool   $isNational = false;

    protected HolidayRepositoryInterface $holidayRepo;

    public function boot(HolidayRepositoryInterface $holidayRepo): void
    {
        $this->holidayRepo = $holidayRepo;
    }

    public function mount(): void
    {
        $this->year = now()->year;
    }

    protected function rules(): array
    {
        return [
            'date'       => ['required', 'date'],
            'name'       => ['required', 'string', 'max:150'],
            'isNational' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'date' => 'tanggal',
            'name' => 'nama hari libur',
        ];
    }

    public function prevYear(): void
    {
        $this->year--;
    }

    public function nextYear(): void
    {
        $this->year++;
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'editingId', 'isNational']);
        $this->date = now()->format('Y-m-d');
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'holiday-form');
    }

    public function openEdit(int $id): void
    {
        $holiday = $this->holidayRepo->findById($id);

        if (! $holiday) {
            return;
        }

        $this->editingId  = $id;
        $this->date       = $holiday->date;
        $this->name       = $holiday->name;
        $this->isNational = (bool) $holiday->is_national;
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'holiday-form');
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'company_id'  => $this->authUser()->company_id,
            'date'        => $this->date,
            'name'        => $this->name,
            'is_national' => $this->isNational,
        ];

        if ($this->editingId) {
            $holiday = $this->holidayRepo->findById($this->editingId);
            $this->holidayRepo->update($holiday, $data);
            $this->dispatch('toast', type: 'success', message: 'Hari libur berhasil diperbarui.');
        } else {
            $this->holidayRepo->create($data);
            $this->dispatch('toast', type: 'success', message: 'Hari libur berhasil ditambahkan.');
        }

        $this->year = (int) date('Y', strtotime($this->date));
        $this->dispatch('close-modal', name: 'holiday-form');
        $this->reset(['name', 'editingId', 'isNational', 'date']);
    }

    public function delete(int $id): void
    {
        $holiday = $this->holidayRepo->findById($id);

        if (! $holiday) {
            return;
        }

        $this->holidayRepo->delete($holiday);
        $this->dispatch('toast', type: 'success', message: 'Hari libur berhasil dihapus.');
    }

    public function render(): View
    {
        $holidays = $this->holidayRepo->getByCompanyAndYear(
            $this->authUser()->company_id,
            $this->year,
        );

        return view('livewire.admin.master.holidays', [
            'holidays' => $holidays,
        ]);
    }
}
