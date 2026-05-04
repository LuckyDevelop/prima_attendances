<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'leave_type'     => new LeaveTypeResource($this->whenLoaded('leaveType')),
            'start_date'     => $this->start_date->format('Y-m-d'),
            'end_date'       => $this->end_date->format('Y-m-d'),
            'total_days'     => $this->total_days,
            'reason'         => $this->reason,
            'attachment_url' => $this->attachment
                ? asset('storage/' . $this->attachment)
                : null,
            'status'         => $this->status->value,
            'submitted_at'   => $this->submitted_at?->format('Y-m-d H:i:s'),
            'created_at'     => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
