<?php

namespace App\Policies;

use App\Models\Contact;

class ContactPolicy extends CrmRecordPolicy
{
    protected function modelClass(): string
    {
        return Contact::class;
    }
}
