<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'device_name' => $this->device_name,
            'platform'    => $this->platform,
            'is_active'   => $this->is_active,
            'last_login'  => $this->last_login?->format('Y-m-d H:i:s'),
        ];
    }
}
