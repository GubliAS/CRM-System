<?php

namespace App\Http\Requests;

use App\Models\SupportCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeCaseOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('case');

        return $case instanceof SupportCase && ($this->user()?->can('changeOwner', $case) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'transfer_activities' => ['sometimes', 'boolean'],
        ];
    }
}
