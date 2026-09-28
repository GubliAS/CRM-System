<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Support\Picklists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead instanceof Lead && ($this->user()?->can('changeStatus', $lead) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lead_status' => ['required', 'string', Rule::in(Picklists::LEAD_STATUSES_EDITABLE)],
        ];
    }
}
