<?php

namespace App\Actions\Cases;

use App\Models\SupportCase;
use App\Models\User;
use LogicException;

class ReopenCase
{
    public function handle(User $actor, SupportCase $case): SupportCase
    {
        if (! $case->is_closed) {
            throw new LogicException('Case is not closed.');
        }

        $case->update([
            'status' => 'Working',
            'updated_by' => $actor->id,
        ]);

        return $case;
    }
}
