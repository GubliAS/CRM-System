<?php

namespace App\Http\Requests;

use App\Models\Opportunity;
use App\Rules\VisibleAccount;
use App\Support\OpportunityStage;
use App\Support\Picklists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Opportunity::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'account_id' => ['required', 'integer', new VisibleAccount],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'decimal:0,2', 'max:9999999999999.99'],
            'close_date' => ['required', 'date', 'after_or_equal:today'],
            'stage' => ['required', 'string', Rule::in(array_keys(OpportunityStage::PROBABILITIES))],
            'type' => ['nullable', 'string', Rule::in(Picklists::OPPORTUNITY_TYPES)],
            'lead_source' => ['nullable', 'string', Rule::in(Picklists::LEAD_SOURCES)],
            'next_step' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount') && $this->input('amount') === '') {
            $this->merge(['amount' => null]);
        }

        if ($this->has('type') && $this->input('type') === '') {
            $this->merge(['type' => null]);
        }

        if ($this->has('lead_source') && $this->input('lead_source') === '') {
            $this->merge(['lead_source' => null]);
        }
    }
}
