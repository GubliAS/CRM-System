<?php

namespace App\Policies;

use App\Models\Opportunity;

class OpportunityPolicy extends CrmRecordPolicy
{
    protected function modelClass(): string
    {
        return Opportunity::class;
    }
}
