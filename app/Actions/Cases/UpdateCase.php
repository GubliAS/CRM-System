<?php

namespace App\Actions\Cases;

use App\Mail\OwnerChangedMail;
use App\Models\SupportCase;
use App\Models\User;
use App\Support\OutboundMail;
use LogicException;

class UpdateCase
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, SupportCase $case, array $attributes): SupportCase
    {
        if ($case->is_closed) {
            throw new LogicException('Closed cases cannot be edited.');
        }

        $previousOwnerId = $case->owner_id;
        $values = ['updated_by' => $actor->id];

        foreach ([
            'contact_id',
            'account_id',
            'subject',
            'description',
            'internal_comments',
            'status',
            'priority',
            'type',
            'origin',
            'reason',
            'web_email',
            'web_name',
            'web_company',
            'web_phone',
        ] as $key) {
            if (array_key_exists($key, $attributes)) {
                $values[$key] = $attributes[$key];
            }
        }

        if (array_key_exists('owner_id', $attributes) && $actor->mayReassignOwner() && filled($attributes['owner_id'])) {
            $values['owner_id'] = $attributes['owner_id'];
        }

        $case->update($values);
        $case = $case->fresh(['owner']);

        $newOwnerId = (int) ($case->owner_id ?? 0);
        if ($newOwnerId > 0 && $newOwnerId !== (int) $previousOwnerId && $case->owner) {
            OutboundMail::queueAndLog(
                $actor,
                $case->owner,
                $case,
                'Owner change notification sent.',
                OwnerChangedMail::class,
                [$case, $case->owner, $actor, 'case '.$case->case_number],
            );
        }

        return $case;
    }
}
