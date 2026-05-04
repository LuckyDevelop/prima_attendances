<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'permission_type' => [
                'value' => $this->permission_type->value,
                'label' => $this->permission_type->label(),
            ],
            'request_date'    => $this->request_date->format('Y-m-d'),
            'start_time'      => $this->start_time,
            'end_time'        => $this->end_time,
            'reason'          => $this->reason,
            'attachment_url'  => $this->attachment
                ? asset('storage/' . $this->attachment)
                : null,
            'status'          => $this->status->value,
            'submitted_at'    => $this->submitted_at?->format('Y-m-d H:i:s'),
            'created_at'      => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
