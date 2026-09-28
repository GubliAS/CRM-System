<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Models\User;

class CreateContact
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, array $attributes): Contact
    {
        return Contact::query()->create([
            ...$this->fields($attributes),
            'owner_id' => $actor->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function fields(array $attributes): array
    {
        return [
            'account_id' => $attributes['account_id'],
            'salutation' => $attributes['salutation'] ?? null,
            'first_name' => $attributes['first_name'] ?? null,
            'last_name' => $attributes['last_name'],
            'title' => $attributes['title'] ?? null,
            'department' => $attributes['department'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'mobile' => $attributes['mobile'] ?? null,
            'home_phone' => $attributes['home_phone'] ?? null,
            'other_phone' => $attributes['other_phone'] ?? null,
            'email' => $attributes['email'] ?? null,
            'fax' => $attributes['fax'] ?? null,
            'reports_to_id' => $attributes['reports_to_id'] ?? null,
            'assistant' => $attributes['assistant'] ?? null,
            'assistant_phone' => $attributes['assistant_phone'] ?? null,
            'mailing_street' => $attributes['mailing_street'] ?? null,
            'mailing_city' => $attributes['mailing_city'] ?? null,
            'mailing_state' => $attributes['mailing_state'] ?? null,
            'mailing_postal_code' => $attributes['mailing_postal_code'] ?? null,
            'mailing_country' => $attributes['mailing_country'] ?? null,
            'other_street' => $attributes['other_street'] ?? null,
            'other_city' => $attributes['other_city'] ?? null,
            'other_state' => $attributes['other_state'] ?? null,
            'other_postal_code' => $attributes['other_postal_code'] ?? null,
            'other_country' => $attributes['other_country'] ?? null,
            'lead_source' => $attributes['lead_source'] ?? null,
            'birthdate' => $attributes['birthdate'] ?? null,
            'description' => $attributes['description'] ?? null,
        ];
    }
}
