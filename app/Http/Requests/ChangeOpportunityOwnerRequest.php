<?php

namespace App\Http\Requests;

use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;

class ChangeOpportunityOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $opportunity = $this->route('opportunity');

        return $opportunity instanceof Opportunity
            && ($this->user()?->can('update', $opportunity) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'owner_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
