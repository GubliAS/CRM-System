<?php

namespace App\Http\Requests;

use App\Models\SupportCase;
use App\Support\Picklists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SupportCase::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'id')],
            'account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')],
            'subject' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'internal_comments' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(Picklists::CASE_STATUSES_OPEN)],
            'priority' => ['nullable', 'string', Rule::in(Picklists::CASE_PRIORITIES)],
            'type' => ['nullable', 'string', Rule::in(Picklists::CASE_TYPES)],
            'origin' => ['required', 'string', Rule::in(Picklists::CASE_ORIGINS)],
            'reason' => ['nullable', 'string', Rule::in(Picklists::CASE_REASONS)],
            'web_email' => ['nullable', 'email', 'max:80'],
            'web_name' => ['nullable', 'string', 'max:80'],
            'web_company' => ['nullable', 'string', 'max:255'],
            'web_phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('status')) {
            $this->merge(['status' => 'New']);
        }

        $this->merge([
            'contact_id' => $this->filled('contact_id') ? $this->input('contact_id') : null,
            'account_id' => $this->filled('account_id') ? $this->input('account_id') : null,
            'priority' => $this->filled('priority') ? $this->input('priority') : null,
            'type' => $this->filled('type') ? $this->input('type') : null,
            'reason' => $this->filled('reason') ? $this->input('reason') : null,
            'subject' => $this->filled('subject') ? $this->input('subject') : null,
            'description' => $this->filled('description') ? $this->input('description') : null,
            'internal_comments' => $this->filled('internal_comments') ? $this->input('internal_comments') : null,
            'web_email' => $this->filled('web_email') ? $this->input('web_email') : null,
            'web_name' => $this->filled('web_name') ? $this->input('web_name') : null,
            'web_company' => $this->filled('web_company') ? $this->input('web_company') : null,
            'web_phone' => $this->filled('web_phone') ? $this->input('web_phone') : null,
        ]);
    }
}
