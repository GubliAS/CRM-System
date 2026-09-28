<?php

namespace App\Actions\Accounts;

use App\Models\Account;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class DeleteAccount
{
    public function handle(Account $account): void
    {
        if ($account->contacts()->exists() || $account->opportunities()->exists()) {
            throw ValidationException::withMessages([
                'delete' => 'Remove related contacts and opportunities before deleting this account.',
            ]);
        }

        try {
            $account->delete();
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'delete' => 'This account cannot be deleted while other records still reference it.',
            ]);
        }
    }
}
