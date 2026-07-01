<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:1000'],
            'abstract' => ['nullable', 'string'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'semantic_scholar_id' => ['nullable', 'string', 'max:64'],
            'raw_metadata' => ['nullable', 'array'],
        ];
    }
}
