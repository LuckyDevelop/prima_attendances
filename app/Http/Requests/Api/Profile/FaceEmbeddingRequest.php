<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Profile;

use Illuminate\Foundation\Http\FormRequest;

class FaceEmbeddingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'embedding' => ['required', 'string', 'max:50000'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($v) {
            $embedding = $this->input('embedding');
            if ($embedding !== null && json_decode($embedding) === null) {
                $v->errors()->add('embedding', 'Data embedding harus berformat JSON yang valid.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'embedding.required' => 'Data embedding wajib diisi.',
        ];
    }
}
