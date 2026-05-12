<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Approval;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.string' => 'Komentar harus berupa teks.',
            'comment.max'    => 'Komentar maksimal 500 karakter.',
        ];
    }
}
