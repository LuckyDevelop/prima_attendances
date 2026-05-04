<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'year'        => $this->year,
            'total_quota' => $this->total_quota,
            'used'        => $this->used,
            'remaining'   => $this->remaining,
            'leave_type'  => new LeaveTypeResource($this->whenLoaded('leaveType')),
        ];
    }
}
