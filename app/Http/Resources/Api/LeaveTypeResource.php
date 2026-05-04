<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'default_quota'       => $this->default_quota,
            'requires_attachment' => $this->requires_attachment,
            'is_paid'             => $this->is_paid,
        ];
    }
}
