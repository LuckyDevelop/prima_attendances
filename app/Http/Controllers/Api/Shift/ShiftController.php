<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Shift;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ShiftResource;
use App\Repositories\Contracts\ShiftRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ShiftRepositoryInterface $shiftRepository,
    ) {}

    public function today(Request $request): JsonResponse
    {
        $assignment = $this->shiftRepository->getTodayAssignment($request->user()->id);

        if ($assignment === null) {
            return $this->success(null, 'Tidak ada shift yang dijadwalkan hari ini.');
        }

        return $this->success(
            new ShiftResource($assignment->shift),
            'Shift hari ini berhasil diambil.'
        );
    }

    public function schedule(Request $request): JsonResponse
    {
        $month = $request->query('month', now()->format('Y-m'));

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->validationError(
                ['month' => ['Format bulan harus YYYY-MM (contoh: 2026-04).']],
                'Data tidak valid.'
            );
        }

        $assignments = $this->shiftRepository->getSchedule($request->user()->id, $month);

        $data = $assignments->map(fn ($a) => [
            'work_date' => $a->work_date->format('Y-m-d'),
            'shift'     => new ShiftResource($a->shift),
        ]);

        return $this->success($data, 'Jadwal shift berhasil diambil.');
    }
}
