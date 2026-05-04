<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'employee_id'        => $this->employee_id,
            'full_name'          => $this->full_name,
            'email'              => $this->email,
            'phone'              => $this->phone,
            'photo_url'          => $this->photo ? asset('storage/' . $this->photo) : null,
            'role'               => $this->role->value,
            'role_label'         => $this->role->label(),
            'status'             => $this->status->value,
            'has_face_embedding' => $this->getRawOriginal('face_embedding') !== null,
            'department'         => $this->whenLoaded('department', fn () => $this->department ? [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ] : null),
            'company'            => $this->whenLoaded('company', fn () => $this->company ? [
                'id'       => $this->company->id,
                'name'     => $this->company->name,
                'timezone' => $this->company->timezone,
            ] : null),
            'office_location'    => $this->whenLoaded('officeLocation', fn () => $this->officeLocation ? [
                'id'      => $this->officeLocation->id,
                'name'    => $this->officeLocation->name,
                'address' => $this->officeLocation->address,
            ] : null),
        ];
    }
}
