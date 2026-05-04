<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Profile;

use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use App\Livewire\Admin\AdminComponent;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Profil Saya')]
class Show extends AdminComponent
{
    use WithFileUploads;

    public bool $editing = false;

    #[Validate('required|string|max:150')]
    public string $fullName = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('nullable|image|mimes:jpg,jpeg,png|max:2048')]
    public $photo = null;

    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditRepo,
    ) {}

    public function mount(): void
    {
        $user           = $this->authUser();
        $this->fullName = $user->full_name;
        $this->phone    = $user->phone ?? '';
    }

    public function startEditing(): void
    {
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->editing = false;
        $this->mount();
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validateOnly('fullName');
        $this->validateOnly('phone');

        DB::beginTransaction();
        try {
            $this->userRepo->update($this->authUser(), [
                'full_name' => $this->fullName,
                'phone'     => $this->phone ?: null,
            ]);

            $this->auditRepo->log(
                auth()->id(),
                'profile_updated',
                'User',
                (string) auth()->id(),
                ['fields' => ['full_name', 'phone']],
                request()->ip(),
            );

            DB::commit();

            $this->editing = false;
            $this->dispatch('toast', type: 'success', message: 'Profil berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profile update failed', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Gagal memperbarui profil.');
        }
    }

    public function uploadPhoto(): void
    {
        $this->validateOnly('photo');

        DB::beginTransaction();
        try {
            $user = $this->authUser();

            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $path = $this->photo->store("photos/{$user->id}", 'public');
            $this->userRepo->updatePhoto($user, $path);
            $this->photo = null;

            $this->auditRepo->log(auth()->id(), 'photo_updated', 'User', (string) $user->id, null, request()->ip());

            DB::commit();

            $this->dispatch('toast', type: 'success', message: 'Foto profil berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Photo upload failed', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Gagal mengunggah foto.');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.profile.show');
    }
}
