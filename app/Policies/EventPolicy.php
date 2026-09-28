<?php

namespace App\Policies;

use App\Models\Event;

class EventPolicy extends CrmRecordPolicy
{
    protected function modelClass(): string
    {
        return Event::class;
    }
}
