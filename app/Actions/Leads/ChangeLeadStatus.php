<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\User;
use LogicException;

class ChangeLeadStatus
{
    public function handle(User $actor, Lead $lead, string $status): Lead
    {
        if ($lead->converted) {
            throw new LogicException('Converted leads cannot change status.');
        }

        $lead->update([
            'lead_status' => $status,
            'updated_by' => $actor->id,
        ]);

        return $lead;
    }
}
