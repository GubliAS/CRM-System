<?php

namespace App\Actions\Opportunities;

use App\Models\Opportunity;
use App\Models\User;

class ArchiveOpportunity
{
    public function handle(User $actor, Opportunity $opportunity): Opportunity
    {
        $opportunity->update([
            'archived_at' => now(),
            'updated_by' => $actor->id,
        ]);

        return $opportunity;
    }
}
