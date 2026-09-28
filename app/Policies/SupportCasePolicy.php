<?php

namespace App\Policies;

use App\Models\SupportCase;

class SupportCasePolicy extends CrmRecordPolicy
{
    protected function modelClass(): string
    {
        return SupportCase::class;
    }
}
