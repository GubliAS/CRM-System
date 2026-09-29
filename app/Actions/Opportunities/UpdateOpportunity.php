<?php

namespace App\Actions\Opportunities;

use App\Mail\OwnerChangedMail;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\User;
use App\Support\OpportunityStage;
use App\Support\OutboundMail;
use Illuminate\Support\Facades\DB;
use LogicException;

class UpdateOpportunity
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, Opportunity $opportunity, array $attributes): Opportunity
    {
        if ($opportunity->archived_at !== null) {
            throw new LogicException('Archived opportunities cannot be edited.');
        }

        $previousOwnerId = $opportunity->owner_id;
        $previousStage = $opportunity->stage;

        return DB::transaction(function () use ($actor, $opportunity, $attributes, $previousOwnerId, $previousStage): Opportunity {
            $values = ['updated_by' => $actor->id];

            foreach ([
                'name',
                'account_id',
                'amount',
                'close_date',
                'stage',
                'type',
                'lead_source',
                'next_step',
                'description',
            ] as $key) {
                if (array_key_exists($key, $attributes)) {
                    $values[$key] = $attributes[$key];
                }
            }

            if (array_key_exists('owner_id', $attributes) && $actor->mayReassignOwner() && filled($attributes['owner_id'])) {
                $values['owner_id'] = $attributes['owner_id'];
            }

            $opportunity->update($values);

            if (array_key_exists('stage', $attributes) && $attributes['stage'] !== $previousStage) {
                OpportunityStageHistory::query()->create([
                    'opportunity_id' => $opportunity->id,
                    'from_stage' => $previousStage,
                    'to_stage' => $opportunity->stage,
                    'probability' => OpportunityStage::probability($opportunity->stage),
                    'user_id' => $actor->id,
                ]);
            }

            $opportunity = $opportunity->fresh(['owner']);

            $newOwnerId = (int) ($opportunity->owner_id ?? 0);
            if ($newOwnerId > 0 && $newOwnerId !== (int) $previousOwnerId && $opportunity->owner) {
                OutboundMail::queueAndLog(
                    $actor,
                    $opportunity->owner,
                    $opportunity,
                    'Owner change notification sent.',
                    OwnerChangedMail::class,
                    [$opportunity, $opportunity->owner, $actor, 'opportunity '.$opportunity->name],
                );
            }

            return $opportunity;
        });
    }
}
