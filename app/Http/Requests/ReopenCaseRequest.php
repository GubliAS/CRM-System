<?php

namespace App\Http\Requests;

use App\Models\SupportCase;
use Illuminate\Foundation\Http\FormRequest;

class ReopenCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof SupportCase && ($this->user()?->can('reopen', $case) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
