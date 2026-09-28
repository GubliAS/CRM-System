<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Models\User;

class UpdateContact
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, Contact $contact, array $attributes): Contact
    {
        $values = ['updated_by' => $actor->id];

        foreach ([
            'account_id',
            'salutation',
            'first_name',
            'last_name',
            'title',
            'department',
            'phone',
            'mobile',
            'home_phone',
            'other_phone',
            'email',
            'fax',
            'reports_to_id',
            'assistant',
            'assistant_phone',
            'mailing_street',
            'mailing_city',
            'mailing_state',
            'mailing_postal_code',
            'mailing_country',
            'other_street',
            'other_city',
            'other_state',
            'other_postal_code',
            'other_country',
            'lead_source',
            'birthdate',
            'description',
        ] as $key) {
            if (array_key_exists($key, $attributes)) {
                $values[$key] = $attributes[$key];
            }
        }

        if (array_key_exists('owner_id', $attributes) && $actor->mayReassignOwner() && filled($attributes['owner_id'])) {
            $values['owner_id'] = $attributes['owner_id'];
        }

        $contact->update($values);

        return $contact;
    }
}
