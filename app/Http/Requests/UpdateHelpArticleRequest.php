<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHelpArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $article = $this->route('help_article');

        return [
            'help_category_id' => ['required', 'exists:help_categories,id'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('help_articles', 'slug')->ignore($article->id), 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'array'],
            'content.*.type' => ['required_with:content', 'string', Rule::in(config('cms.block_types', []))],
            'sort' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug must be in kebab-case format (lowercase letters, numbers, and hyphens).',
        ];
    }
}
