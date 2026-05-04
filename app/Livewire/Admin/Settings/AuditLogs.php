<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Audit Log')]
class AuditLogs extends AdminComponent
{
    use WithPagination;

    #[Url]
    public string $entity = '';

    #[Url]
    public string $action = '';

    #[Url]
    public string $userSearch = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public int $perPage = 20;

    protected AuditLogRepositoryInterface $auditRepo;

    public function boot(AuditLogRepositoryInterface $auditRepo): void
    {
        $this->auditRepo = $auditRepo;
    }

    public function updatedEntity(): void    { $this->resetPage(); }
    public function updatedAction(): void    { $this->resetPage(); }
    public function updatedUserSearch(): void{ $this->resetPage(); }
    public function updatedDateFrom(): void  { $this->resetPage(); }
    public function updatedDateTo(): void    { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->entity     = '';
        $this->action     = '';
        $this->userSearch = '';
        $this->dateFrom   = '';
        $this->dateTo     = '';
        $this->resetPage();
    }

    public function getLogs(): LengthAwarePaginator
    {
        return $this->auditRepo->paginateForAdmin($this->authUser()->company_id, [
            'entity'      => $this->entity ?: null,
            'action'      => $this->action ?: null,
            'user_search' => $this->userSearch ?: null,
            'date_from'   => $this->dateFrom ?: null,
            'date_to'     => $this->dateTo ?: null,
            'per_page'    => $this->perPage,
        ]);
    }

    public function getEntityOptions(): array
    {
        return ['attendance', 'leave_request', 'permission_request', 'user', 'company', 'shift'];
    }

    public function render(): View
    {
        return view('livewire.admin.settings.audit-logs', [
            'logs'          => $this->getLogs(),
            'entityOptions' => $this->getEntityOptions(),
        ]);
    }
}
