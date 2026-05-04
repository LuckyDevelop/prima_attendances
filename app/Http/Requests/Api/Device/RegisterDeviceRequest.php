<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Device;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id'   => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:150'],
            'platform'    => ['required', 'in:android,ios'],
            'fcm_token'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.in' => 'Platform harus android atau ios.',
        ];
    }
}
