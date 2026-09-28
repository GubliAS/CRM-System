<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Support\Picklists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lead::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'salutation' => ['nullable', 'string', Rule::in(Picklists::SALUTATIONS)],
            'first_name' => ['nullable', 'string', 'max:40'],
            'last_name' => ['required', 'string', 'max:80'],
            'company' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:128'],
            'email' => ['nullable', 'email', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'lead_status' => ['required', 'string', Rule::in(Picklists::LEAD_STATUSES_EDITABLE)],
            'lead_source' => ['nullable', 'string', Rule::in(Picklists::LEAD_SOURCES)],
            'rating' => ['nullable', 'string', Rule::in(Picklists::LEAD_RATINGS)],
            'industry' => ['nullable', 'string', 'max:80'],
            'annual_revenue' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'number_of_employees' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'website' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('lead_status')) {
            $this->merge(['lead_status' => 'New']);
        }
    }
}
