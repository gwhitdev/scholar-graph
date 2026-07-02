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
            'openalex_id' => ['nullable', 'string', 'max:64'],
            'authors' => ['nullable', 'array'],
            'authors.*' => ['string', 'max:500'],
            'doi' => ['nullable', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:500'],
            'cited_by_count' => ['nullable', 'integer', 'min:0'],
            'referenced_works' => ['nullable', 'array'],
            'referenced_works.*' => ['string', 'max:64'],
        ];
    }
}
