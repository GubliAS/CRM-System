<?php

namespace App\Actions\Leads;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class ChangeLeadOwner
{
    /**
     * @param  array{owner_id: int, transfer_activities?: bool}  $attributes
     */
    public function handle(User $actor, Lead $lead, array $attributes): Lead
    {
        if ($lead->converted) {
            throw new LogicException('Converted leads cannot change owner.');
        }

        $newOwnerId = (int) $attributes['owner_id'];
        $previousOwnerId = $lead->owner_id;
        $transfer = (bool) ($attributes['transfer_activities'] ?? false);

        return DB::transaction(function () use ($actor, $lead, $newOwnerId, $previousOwnerId, $transfer): Lead {
            $lead->update([
                'owner_id' => $newOwnerId,
                'updated_by' => $actor->id,
            ]);

            if ($transfer) {
                Task::query()
                    ->where('related_type', Lead::class)
                    ->where('related_id', $lead->id)
                    ->where('status', '!=', 'Completed')
                    ->update([
                        'owner_id' => $newOwnerId,
                        'assigned_to_id' => $newOwnerId,
                        'updated_by' => $actor->id,
                    ]);

                Event::query()
                    ->where('related_type', Lead::class)
                    ->where('related_id', $lead->id)
                    ->where('ends_at', '>=', now())
                    ->update([
                        'owner_id' => $newOwnerId,
                        'assigned_to_id' => $newOwnerId,
                        'updated_by' => $actor->id,
                    ]);
            }

            ActivityLog::query()->create([
                'subject_type' => Lead::class,
                'subject_id' => $lead->id,
                'user_id' => $actor->id,
                'action' => 'owner_changed',
                'description' => 'Lead owner changed.',
                'properties' => [
                    'previous_owner_id' => $previousOwnerId,
                    'new_owner_id' => $newOwnerId,
                    'transfer_activities' => $transfer,
                ],
            ]);

            return $lead->fresh();
        });
    }
}
