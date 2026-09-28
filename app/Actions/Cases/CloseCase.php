<?php

namespace App\Actions\Cases;

use App\Models\SupportCase;
use App\Models\User;
use LogicException;

class CloseCase
{
    public function handle(User $actor, SupportCase $case): SupportCase
    {
        if ($case->is_closed) {
            throw new LogicException('Case is already closed.');
        }

        $case->update([
            'status' => SupportCase::STATUS_CLOSED,
            'updated_by' => $actor->id,
        ]);

        return $case;
    }
}
