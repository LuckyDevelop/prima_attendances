<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Permission;

use App\Enums\PermissionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_type' => ['required', Rule::enum(PermissionType::class)],
            'request_date'    => ['required', 'date', 'after_or_equal:today'],
            'start_time'      => ['nullable', 'date_format:H:i'],
            'end_time'        => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'reason'          => ['nullable', 'string', 'max:500'],
            'attachment'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'permission_type.required'    => 'Jenis izin wajib dipilih.',
            'permission_type.enum'        => 'Jenis izin tidak valid.',
            'request_date.required'       => 'Tanggal izin wajib diisi.',
            'request_date.after_or_equal' => 'Tanggal izin tidak boleh di masa lalu.',
            'start_time.date_format'      => 'Format jam mulai harus HH:MM.',
            'end_time.date_format'        => 'Format jam selesai harus HH:MM.',
            'end_time.after_or_equal'     => 'Jam selesai tidak boleh sebelum jam mulai.',
            'attachment.mimes'            => 'Format lampiran harus pdf, jpg, jpeg, atau png.',
            'attachment.max'              => 'Ukuran lampiran maksimal 2MB.',
        ];
    }
}
