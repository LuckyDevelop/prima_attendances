<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NotificationResource;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'is_read'  => $request->has('is_read') ? $request->boolean('is_read') : null,
            'type'     => $request->query('type'),
            'per_page' => $request->query('per_page', 15),
        ];

        $paginator   = $this->notificationRepository->paginateByUser($request->user()->id, $filters);
        $items       = NotificationResource::collection($paginator->items())->collection;
        $unreadCount = $this->notificationRepository->getUnreadCount($request->user()->id);

        $paginator->setCollection($items);

        $response = $this->successPaginated($paginator, 'Notifikasi berhasil diambil.', [
            'unread_count' => $unreadCount,
        ]);

        return $response;
    }

    public function read(Request $request, int $id): JsonResponse
    {
        $notification = $this->notificationRepository->findForUser($id, $request->user()->id);

        if ($notification === null) {
            return $this->notFound('Notifikasi tidak ditemukan.');
        }

        $this->notificationRepository->markAsRead($notification);

        return $this->success(null, 'Notifikasi ditandai sebagai dibaca.');
    }

    public function readAll(Request $request): JsonResponse
    {
        $count = $this->notificationRepository->markAllAsRead($request->user()->id);

        return $this->success(['marked_count' => $count], 'Semua notifikasi ditandai sebagai dibaca.');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationRepository->getUnreadCount($request->user()->id);

        return $this->success(['unread_count' => $count], 'Jumlah notifikasi belum dibaca berhasil diambil.');
    }
}
