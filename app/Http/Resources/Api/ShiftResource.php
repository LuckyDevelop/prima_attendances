<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'start_time'     => $this->start_time,
            'end_time'       => $this->end_time,
            'break_minutes'  => $this->break_minutes,
        ];
    }
}
