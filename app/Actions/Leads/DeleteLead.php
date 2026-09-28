<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use LogicException;

class DeleteLead
{
    public function handle(Lead $lead): void
    {
        if ($lead->converted) {
            throw new LogicException('Converted leads cannot be deleted.');
        }

        $lead->delete();
    }
}
