<?php

namespace App\Http\Requests;

use App\Support\GlobalSearch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in(['all', ...GlobalSearch::OBJECT_TYPES])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
