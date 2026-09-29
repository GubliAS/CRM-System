<?php

namespace App\Actions\Leads;

use App\Mail\OwnerChangedMail;
use App\Models\Lead;
use App\Models\User;
use App\Support\OutboundMail;
use LogicException;

class UpdateLead
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, Lead $lead, array $attributes): Lead
    {
        if ($lead->converted) {
            throw new LogicException('Converted leads cannot be edited.');
        }

        $previousOwnerId = $lead->owner_id;
        $values = ['updated_by' => $actor->id];

        foreach ([
            'salutation',
            'first_name',
            'last_name',
            'company',
            'title',
            'email',
            'phone',
            'mobile',
            'lead_status',
            'lead_source',
            'rating',
            'industry',
            'annual_revenue',
            'number_of_employees',
            'website',
            'street',
            'city',
            'state',
            'postal_code',
            'country',
            'description',
        ] as $key) {
            if (array_key_exists($key, $attributes)) {
                $values[$key] = $attributes[$key];
            }
        }

        if (array_key_exists('owner_id', $attributes) && $actor->mayReassignOwner() && filled($attributes['owner_id'])) {
            $values['owner_id'] = $attributes['owner_id'];
        }

        $lead->update($values);
        $lead = $lead->fresh(['owner']);

        $newOwnerId = (int) ($lead->owner_id ?? 0);
        if ($newOwnerId > 0 && $newOwnerId !== (int) $previousOwnerId && $lead->owner) {
            $label = trim(($lead->first_name ? $lead->first_name.' ' : '').$lead->last_name);
            OutboundMail::queueAndLog(
                $actor,
                $lead->owner,
                $lead,
                'Owner change notification sent.',
                OwnerChangedMail::class,
                [$lead, $lead->owner, $actor, 'lead '.$label],
            );
        }

        return $lead;
    }
}
