<?php

namespace App\Actions\Opportunities;

use App\Models\Opportunity;
use App\Models\User;

class ChangeOpportunityOwner
{
    public function handle(User $actor, Opportunity $opportunity, int $ownerId): Opportunity
    {
        $opportunity->update([
            'owner_id' => $ownerId,
            'updated_by' => $actor->id,
        ]);

        return $opportunity;
    }
}
