<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Master;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\OfficeLocationRepositoryInterface;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Lokasi Kantor')]
class OfficeLocations extends AdminComponent
{
    public ?int $editingId = null;

    public string $name         = '';
    public string $address      = '';
    public string $latitude     = '';
    public string $longitude    = '';
    public int    $radiusMeters = 100;
    public bool   $isActive     = true;

    protected OfficeLocationRepositoryInterface $locationRepo;

    public function boot(OfficeLocationRepositoryInterface $locationRepo): void
    {
        $this->locationRepo = $locationRepo;
    }

    protected function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:150'],
            'address'      => ['nullable', 'string', 'max:500'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180'],
            'radiusMeters' => ['required', 'integer', 'min:10', 'max:5000'],
            'isActive'     => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name'         => 'nama lokasi',
            'latitude'     => 'latitude',
            'longitude'    => 'longitude',
            'radiusMeters' => 'radius',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'address', 'latitude', 'longitude', 'editingId']);
        $this->radiusMeters = 100;
        $this->isActive     = true;
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'location-form');
        $this->dispatch('map-init', lat: -6.2, lng: 106.816, radius: 100);
    }

    public function openEdit(int $id): void
    {
        $loc = $this->locationRepo->getById($id);

        if (! $loc) {
            return;
        }

        $this->editingId    = $id;
        $this->name         = $loc->name;
        $this->address      = $loc->address ?? '';
        $this->latitude     = (string) ($loc->latitude ?? '');
        $this->longitude    = (string) ($loc->longitude ?? '');
        $this->radiusMeters = $loc->radius_meters;
        $this->isActive     = (bool) $loc->is_active;
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'location-form');
        $this->dispatch('map-init',
            lat: (float) ($loc->latitude ?? -6.2),
            lng: (float) ($loc->longitude ?? 106.816),
            radius: $loc->radius_meters,
        );
    }

    public function updateCoords(float $lat, float $lng): void
    {
        $this->latitude  = (string) $lat;
        $this->longitude = (string) $lng;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'company_id'    => $this->authUser()->company_id,
            'name'          => $this->name,
            'address'       => $this->address ?: null,
            'latitude'      => $this->latitude !== '' ? (float) $this->latitude : null,
            'longitude'     => $this->longitude !== '' ? (float) $this->longitude : null,
            'radius_meters' => $this->radiusMeters,
            'is_active'     => $this->isActive,
        ];

        if ($this->editingId) {
            $loc = $this->locationRepo->getById($this->editingId);
            $this->locationRepo->update($loc, $data);
            $this->dispatch('toast', type: 'success', message: 'Lokasi kantor berhasil diperbarui.');
        } else {
            $this->locationRepo->create($data);
            $this->dispatch('toast', type: 'success', message: 'Lokasi kantor berhasil ditambahkan.');
        }

        $this->dispatch('close-modal', name: 'location-form');
        $this->reset(['name', 'address', 'latitude', 'longitude', 'editingId']);
    }

    public function delete(int $id): void
    {
        $loc = $this->locationRepo->getById($id);

        if (! $loc || $loc->company_id !== $this->authUser()->company_id) {
            return;
        }

        $this->locationRepo->delete($loc);
        $this->dispatch('toast', type: 'success', message: 'Lokasi kantor berhasil dihapus.');
    }

    public function render(): View
    {
        return view('livewire.admin.master.office-locations', [
            'locations' => $this->locationRepo->getAllByCompany($this->authUser()->company_id),
        ]);
    }
}
