<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Device\RegisterDeviceRequest;
use App\Http\Requests\Api\Device\UpdateFcmTokenRequest;
use App\Http\Resources\Api\DeviceResource;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DeviceRepositoryInterface $deviceRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $devices = $this->deviceRepository->getByUser($request->user()->id);

        return $this->success(
            DeviceResource::collection($devices),
            'Daftar perangkat berhasil diambil.'
        );
    }

    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        $device = $this->deviceRepository->upsert($request->user(), $request->validated());

        return $this->success(
            new DeviceResource($device),
            'Perangkat berhasil didaftarkan.',
            201
        );
    }

    public function updateFcmToken(UpdateFcmTokenRequest $request): JsonResponse
    {
        $updated = $this->deviceRepository->updateFcmToken(
            $request->user()->id,
            $request->device_id,
            $request->fcm_token
        );

        if (!$updated) {
            return $this->notFound('Perangkat tidak ditemukan.');
        }

        return $this->success(null, 'FCM token berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $device = $this->deviceRepository->findForUser($id, $request->user()->id);

        if ($device === null) {
            return $this->notFound('Perangkat tidak ditemukan.');
        }

        $this->deviceRepository->deactivate($device);

        return $this->success(null, 'Perangkat berhasil dinonaktifkan.');
    }
}
