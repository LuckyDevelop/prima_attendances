<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $checkInTime = $this->check_in_time;
        $shiftAssignment = $this->user?->shiftAssignments()
            ->where('work_date', $this->work_date)
            ->with('shift')
            ->first();
        $shift = $shiftAssignment?->shift;

        $lateMinutes = 0;
        if ($checkInTime && $shift) {
            $shiftStart = $checkInTime->clone()->setTimeFromTimeString($shift->start_time);
            $lateMinutes = max(0, $checkInTime->diffInMinutes($shiftStart) - ($this->user?->company?->late_tolerance_min ?? 0));
        }

        return [
            'id'               => $this->id,
            'work_date'        => $this->work_date->format('Y-m-d'),
            'status'           => $this->status->value,
            'can_check_in'     => $this->check_in_time === null,
            'can_check_out'    => $this->check_in_time !== null && $this->check_out_time === null,
            'check_in'         => $checkInTime ? [
                'time'              => $checkInTime->format('H:i:s'),
                'datetime'          => $checkInTime->format('Y-m-d H:i:s'),
                'latitude'          => $this->check_in_lat,
                'longitude'         => $this->check_in_lng,
                'selfie_url'        => $this->check_in_selfie ? asset('storage/' . $this->check_in_selfie) : null,
                'is_late'           => $lateMinutes > 0,
                'late_minutes'      => $lateMinutes,
                'is_mock_location'  => $this->is_mock_location,
                'face_verified'     => $this->face_verified,
                'office_location'   => $this->whenLoaded('officeLocation', fn () => $this->officeLocation ? [
                    'id'      => $this->officeLocation->id,
                    'name'    => $this->officeLocation->name,
                    'address' => $this->officeLocation->address,
                ] : null),
            ] : null,
            'check_out'        => $this->check_out_time ? [
                'time'          => $this->check_out_time->format('H:i:s'),
                'datetime'      => $this->check_out_time->format('Y-m-d H:i:s'),
                'selfie_url'    => $this->check_out_selfie ? asset('storage/' . $this->check_out_selfie) : null,
                'is_early_leave' => false,
            ] : null,
            'work_duration_min' => $this->work_duration_min,
            'notes'            => $this->notes,
        ];
    }
}
