<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Notification;

use App\Enums\NotificationType;
use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Notifikasi')]
class Index extends AdminComponent
{
    use WithPagination;

    #[Url]
    public string $type = '';

    #[Url]
    public string $readFilter = ''; // '' | 'unread' | 'read'

    public int $perPage = 20;

    protected NotificationRepositoryInterface $notifRepo;

    public function boot(NotificationRepositoryInterface $notifRepo): void
    {
        $this->notifRepo = $notifRepo;
    }

    public function updatedType(): void       { $this->resetPage(); }
    public function updatedReadFilter(): void { $this->resetPage(); }

    public function getNotifications(): LengthAwarePaginator
    {
        $filters = ['per_page' => $this->perPage];

        if ($this->type !== '') {
            $filters['type'] = $this->type;
        }

        if ($this->readFilter === 'unread') {
            $filters['is_read'] = false;
        } elseif ($this->readFilter === 'read') {
            $filters['is_read'] = true;
        }

        return $this->notifRepo->paginateByUser($this->authUser()->id, $filters);
    }

    public function markRead(int $id): void
    {
        $notif = $this->notifRepo->findForUser($id, $this->authUser()->id);

        if ($notif && ! $notif->is_read) {
            $this->notifRepo->markAsRead($notif);
        }
    }

    public function markAllRead(): void
    {
        $count = $this->notifRepo->markAllAsRead($this->authUser()->id);
        $this->dispatch('toast', type: 'success', message: "{$count} notifikasi ditandai sudah dibaca.");
    }

    public function getUnreadCount(): int
    {
        return $this->notifRepo->getUnreadCount($this->authUser()->id);
    }

    public function render(): View
    {
        $notifications = $this->getNotifications();
        $unreadCount   = $this->getUnreadCount();

        return view('livewire.admin.notification.index', compact('notifications', 'unreadCount'));
    }
}
