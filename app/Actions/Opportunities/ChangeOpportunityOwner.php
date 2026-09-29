<?php

namespace App\Actions\Opportunities;

use App\Mail\OwnerChangedMail;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use App\Support\OutboundMail;
use Illuminate\Support\Facades\DB;
use LogicException;

class ChangeOpportunityOwner
{
    /**
     * @param  array{owner_id: int, transfer_activities?: bool}  $attributes
     */
    public function handle(User $actor, Opportunity $opportunity, array $attributes): Opportunity
    {
        if ($opportunity->archived_at !== null) {
            throw new LogicException('Archived opportunities cannot change owner.');
        }

        $newOwnerId = (int) $attributes['owner_id'];
        $previousOwnerId = $opportunity->owner_id;
        $transfer = (bool) ($attributes['transfer_activities'] ?? false);

        return DB::transaction(function () use ($actor, $opportunity, $newOwnerId, $previousOwnerId, $transfer): Opportunity {
            $opportunity->update([
                'owner_id' => $newOwnerId,
                'updated_by' => $actor->id,
            ]);

            if ($transfer) {
                Task::query()
                    ->where('related_type', Opportunity::class)
                    ->where('related_id', $opportunity->id)
                    ->where('status', '!=', 'Completed')
                    ->update([
                        'owner_id' => $newOwnerId,
                        'assigned_to_id' => $newOwnerId,
                        'updated_by' => $actor->id,
                    ]);

                Event::query()
                    ->where('related_type', Opportunity::class)
                    ->where('related_id', $opportunity->id)
                    ->where('ends_at', '>=', now())
                    ->update([
                        'owner_id' => $newOwnerId,
                        'assigned_to_id' => $newOwnerId,
                        'updated_by' => $actor->id,
                    ]);
            }

            ActivityLog::query()->create([
                'subject_type' => Opportunity::class,
                'subject_id' => $opportunity->id,
                'user_id' => $actor->id,
                'action' => 'owner_changed',
                'description' => 'Opportunity owner changed.',
                'properties' => [
                    'previous_owner_id' => $previousOwnerId,
                    'new_owner_id' => $newOwnerId,
                    'transfer_activities' => $transfer,
                ],
            ]);

            $opportunity = $opportunity->fresh(['owner']);

            if ($newOwnerId !== (int) $previousOwnerId && $opportunity?->owner) {
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
