<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Leave;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date', 'after_or_equal:today'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['nullable', 'string', 'max:500'],
            'attachment'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_type_id.required'    => 'Jenis cuti wajib dipilih.',
            'leave_type_id.exists'      => 'Jenis cuti tidak ditemukan.',
            'start_date.required'       => 'Tanggal mulai wajib diisi.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu.',
            'end_date.required'         => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal'   => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'attachment.mimes'          => 'Format lampiran harus pdf, jpg, jpeg, atau png.',
            'attachment.max'            => 'Ukuran lampiran maksimal 2MB.',
        ];
    }
}
