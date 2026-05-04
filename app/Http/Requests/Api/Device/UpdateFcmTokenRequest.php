<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Device;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFcmTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string'],
            'fcm_token' => ['required', 'string'],
        ];
    }
}
