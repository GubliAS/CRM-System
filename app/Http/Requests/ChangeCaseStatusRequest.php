<?php

namespace App\Http\Requests;

use App\Models\SupportCase;
use App\Support\Picklists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeCaseStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof SupportCase && ($this->user()?->can('changeStatus', $case) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Picklists::CASE_STATUSES_OPEN)],
        ];
    }
}
