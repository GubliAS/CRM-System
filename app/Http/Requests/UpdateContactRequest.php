<?php

namespace App\Http\Requests;

use App\Models\Contact;
use App\Rules\AllowedOwnerChange;
use App\Rules\VisibleAccount;
use App\Rules\VisibleContact;
use App\Support\Picklists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $contact instanceof Contact && ($this->user()?->can('update', $contact) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Contact $contact */
        $contact = $this->route('contact');

        return [
            'last_name' => ['required', 'string', 'max:80'],
            'account_id' => ['required', 'integer', new VisibleAccount],
            'salutation' => ['nullable', 'string', Rule::in(Picklists::SALUTATIONS)],
            'first_name' => ['nullable', 'string', 'max:40'],
            'title' => ['nullable', 'string', 'max:128'],
            'department' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'home_phone' => ['nullable', 'string', 'max:40'],
            'other_phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:80'],
            'fax' => ['nullable', 'string', 'max:40'],
            'reports_to_id' => ['nullable', 'integer', new VisibleContact($contact->id)],
            'assistant' => ['nullable', 'string', 'max:80'],
            'assistant_phone' => ['nullable', 'string', 'max:40'],
            'mailing_street' => ['nullable', 'string', 'max:255'],
            'mailing_city' => ['nullable', 'string', 'max:80'],
            'mailing_state' => ['nullable', 'string', 'max:80'],
            'mailing_postal_code' => ['nullable', 'string', 'max:80'],
            'mailing_country' => ['nullable', 'string', 'max:80'],
            'other_street' => ['nullable', 'string', 'max:255'],
            'other_city' => ['nullable', 'string', 'max:80'],
            'other_state' => ['nullable', 'string', 'max:80'],
            'other_postal_code' => ['nullable', 'string', 'max:80'],
            'other_country' => ['nullable', 'string', 'max:80'],
            'lead_source' => ['nullable', 'string', 'max:40'],
            'birthdate' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'owner_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id'), new AllowedOwnerChange($contact->owner_id)],
        ];
    }
}
