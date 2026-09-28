<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class DeleteContact
{
    public function handle(Contact $contact): void
    {
        try {
            $contact->delete();
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'delete' => 'This contact cannot be deleted while other records still reference it.',
            ]);
        }
    }
}
