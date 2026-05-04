<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude'         => ['required', 'numeric', 'between:-90,90'],
            'longitude'        => ['required', 'numeric', 'between:-180,180'],
            'is_mock_location' => ['required', 'boolean'],
            'face_verified'    => ['required', 'boolean'],
            'selfie'           => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required'         => 'Latitude wajib diisi.',
            'longitude.required'        => 'Longitude wajib diisi.',
            'is_mock_location.required' => 'Status mock location wajib diisi.',
            'face_verified.required'    => 'Status verifikasi wajah wajib diisi.',
            'selfie.required'           => 'Foto selfie wajib diunggah.',
            'selfie.image'              => 'File harus berupa gambar.',
            'selfie.mimes'              => 'Format gambar harus jpg, jpeg, atau png.',
            'selfie.max'                => 'Ukuran gambar maksimal 5MB.',
        ];
    }
}
