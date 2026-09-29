<?php

namespace App\Actions\Opportunities;

use App\Models\Opportunity;
use App\Models\User;
use LogicException;

class ArchiveOpportunity
{
    public function handle(User $actor, Opportunity $opportunity): Opportunity
    {
        if ($opportunity->archived_at !== null) {
            throw new LogicException('Opportunity is already archived.');
        }

        $opportunity->update([
            'archived_at' => now(),
            'updated_by' => $actor->id,
        ]);

        return $opportunity->fresh();
    }
}
