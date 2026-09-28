<?php

namespace App\Policies;

use App\Models\Account;

class AccountPolicy extends CrmRecordPolicy
{
    protected function modelClass(): string
    {
        return Account::class;
    }
}
