<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportPmtilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.*.file' => 'Each uploaded item must be a file.',
        ];
    }
}
