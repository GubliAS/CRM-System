<?php

namespace App\Actions\Cases;

use App\Models\SupportCase;
use App\Models\User;
use LogicException;

class ChangeCaseStatus
{
    public function handle(User $actor, SupportCase $case, string $status): SupportCase
    {
        if ($case->is_closed) {
            throw new LogicException('Closed cases cannot change status.');
        }

        $case->update([
            'status' => $status,
            'updated_by' => $actor->id,
        ]);

        return $case;
    }
}
