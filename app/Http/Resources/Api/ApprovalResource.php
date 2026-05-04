<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'request_type' => $this->request_type->value,
            'request_id'   => $this->request_id,
            'approver'     => new UserResource($this->whenLoaded('approver')),
            'level'        => $this->level,
            'decision'     => [
                'value' => $this->decision->value,
                'label' => $this->decision->label(),
            ],
            'comment'      => $this->comment,
            'decided_at'   => $this->decided_at?->format('Y-m-d H:i:s'),
        ];
    }
}
