<?php

namespace App\Actions\Cases;

use App\Models\SupportCase;
use LogicException;

class DeleteCase
{
    public function handle(SupportCase $case): void
    {
        if ($case->is_closed) {
            throw new LogicException('Closed cases cannot be deleted.');
        }

        $case->delete();
    }
}
