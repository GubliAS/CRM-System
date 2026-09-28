<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Rules\VisibleAccount;
use App\Support\OpportunityStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead instanceof Lead && ($this->user()?->can('convert', $lead) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_mode' => ['required', 'string', Rule::in(['new', 'existing'])],
            'account_id' => [
                'nullable',
                'required_if:account_mode,existing',
                'integer',
                new VisibleAccount,
            ],
            'create_opportunity' => ['sometimes', 'boolean'],
            'opportunity_name' => ['nullable', 'required_if:create_opportunity,1', 'required_if:create_opportunity,true', 'string', 'max:120'],
            'opportunity_amount' => ['nullable', 'numeric', 'min:0.01', 'decimal:0,2', 'max:9999999999999.99'],
            'opportunity_close_date' => [
                'nullable',
                'required_if:create_opportunity,1',
                'required_if:create_opportunity,true',
                'date',
                'after_or_equal:today',
            ],
            'opportunity_stage' => [
                'nullable',
                'required_if:create_opportunity,1',
                'required_if:create_opportunity,true',
                'string',
                Rule::in(array_keys(OpportunityStage::PROBABILITIES)),
            ],
            'transfer_activities' => ['sometimes', 'boolean'],
        ];
    }
}
