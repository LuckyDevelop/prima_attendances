<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Holiday;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\HolidayResource;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly HolidayRepositoryInterface $holidayRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $year  = (int) $request->query('year', now()->year);
        $month = $request->query('month') ? (int) $request->query('month') : null;

        $holidays = $this->holidayRepository->getByCompanyAndYear(
            $request->user()->company_id,
            $year,
            $month
        );

        return $this->success(
            HolidayResource::collection($holidays),
            'Data hari libur berhasil diambil.'
        );
    }
}
