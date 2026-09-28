<?php

namespace App\Policies;

use App\Models\Lead;

class LeadPolicy extends CrmRecordPolicy
{
    protected function modelClass(): string
    {
        return Lead::class;
    }
}
