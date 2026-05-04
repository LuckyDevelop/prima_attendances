<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string', 'min:8'],
            'device_name' => ['required', 'string', 'max:150'],
            'device_id'   => ['required', 'string'],
            'platform'    => ['required', 'string', 'in:android,ios'],
            'fcm_token'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'       => 'Email wajib diisi.',
            'email.email'          => 'Format email tidak valid.',
            'password.required'    => 'Password wajib diisi.',
            'password.min'         => 'Password minimal 8 karakter.',
            'device_name.required' => 'Nama perangkat wajib diisi.',
            'device_id.required'   => 'ID perangkat wajib diisi.',
            'platform.required'    => 'Platform wajib diisi.',
            'platform.in'          => 'Platform harus android atau ios.',
        ];
    }
}
