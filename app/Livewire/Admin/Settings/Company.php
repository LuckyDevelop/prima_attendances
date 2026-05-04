<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Pengaturan Perusahaan')]
class Company extends AdminComponent
{
    use WithFileUploads;

    public string $name            = '';
    public string $timezone        = 'Asia/Jakarta';
    public int    $lateTolerance   = 0;
    public ?string $logoPath       = null;
    public mixed  $logo            = null; // Livewire temp upload

    protected CompanyRepositoryInterface $companyRepo;

    public function boot(CompanyRepositoryInterface $companyRepo): void
    {
        $this->companyRepo = $companyRepo;
    }

    public function mount(): void
    {
        $company = $this->companyRepo->find($this->authUser()->company_id);

        abort_if(! $company, 404);

        $this->name          = $company->name;
        $this->timezone      = $company->timezone ?? 'Asia/Jakarta';
        $this->lateTolerance = $company->late_tolerance_min ?? 0;
        $this->logoPath      = $company->logo;
    }

    protected function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:150'],
            'timezone'      => ['required', 'string', 'timezone'],
            'lateTolerance' => ['required', 'integer', 'min:0', 'max:120'],
            'logo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name'          => 'nama perusahaan',
            'timezone'      => 'timezone',
            'lateTolerance' => 'toleransi keterlambatan',
            'logo'          => 'logo',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $company = $this->companyRepo->find($this->authUser()->company_id);

        abort_if(! $company, 404);

        try {
            if ($this->logo) {
                if ($this->logoPath) {
                    Storage::disk('public')->delete($this->logoPath);
                }
                $this->logoPath = $this->logo->store('logos', 'public');
                $this->companyRepo->updateLogo($company, $this->logoPath);
                $company->refresh();
            }

            $this->companyRepo->update($company, [
                'name'               => $this->name,
                'timezone'           => $this->timezone,
                'late_tolerance_min' => $this->lateTolerance,
            ]);

            $this->logo = null;
            $this->dispatch('toast', type: 'success', message: 'Pengaturan perusahaan berhasil disimpan.');

        } catch (\Exception $e) {
            Log::error('Company settings update failed', [
                'admin_id'   => $this->authUser()->id,
                'company_id' => $this->authUser()->company_id,
                'error'      => $e->getMessage(),
            ]);
            $this->dispatch('toast', type: 'error', message: 'Gagal menyimpan pengaturan. Silakan coba lagi.');
        }
    }

    public function removeLogo(): void
    {
        $company = $this->companyRepo->find($this->authUser()->company_id);

        abort_if(! $company, 404);

        if ($this->logoPath) {
            Storage::disk('public')->delete($this->logoPath);
            $this->companyRepo->updateLogo($company, null);
            $this->logoPath = null;
        }

        $this->dispatch('toast', type: 'success', message: 'Logo berhasil dihapus.');
    }

    public function getTimezones(): array
    {
        return \DateTimeZone::listIdentifiers(\DateTimeZone::ASIA);
    }

    public function render(): View
    {
        return view('livewire.admin.settings.company', [
            'timezones' => $this->getTimezones(),
        ]);
    }
}
