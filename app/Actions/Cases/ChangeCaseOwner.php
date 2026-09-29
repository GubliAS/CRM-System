<?php

namespace App\Actions\Cases;

use App\Mail\OwnerChangedMail;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\User;
use App\Support\OutboundMail;
use Illuminate\Support\Facades\DB;
use LogicException;

class ChangeCaseOwner
{
    /**
     * @param  array{owner_id: int, transfer_activities?: bool}  $attributes
     */
    public function handle(User $actor, SupportCase $case, array $attributes): SupportCase
    {
        if ($case->is_closed) {
            throw new LogicException('Closed cases cannot change owner.');
        }

        $newOwnerId = (int) $attributes['owner_id'];
        $previousOwnerId = $case->owner_id;
        $transfer = (bool) ($attributes['transfer_activities'] ?? false);

        return DB::transaction(function () use ($actor, $case, $newOwnerId, $previousOwnerId, $transfer): SupportCase {
            $case->update([
                'owner_id' => $newOwnerId,
                'updated_by' => $actor->id,
            ]);

            if ($transfer) {
                Task::query()
                    ->where('related_type', SupportCase::class)
                    ->where('related_id', $case->id)
                    ->where('status', '!=', 'Completed')
                    ->update([
                        'owner_id' => $newOwnerId,
                        'assigned_to_id' => $newOwnerId,
                        'updated_by' => $actor->id,
                    ]);

                Event::query()
                    ->where('related_type', SupportCase::class)
                    ->where('related_id', $case->id)
                    ->where('ends_at', '>=', now())
                    ->update([
                        'owner_id' => $newOwnerId,
                        'assigned_to_id' => $newOwnerId,
                        'updated_by' => $actor->id,
                    ]);
            }

            ActivityLog::query()->create([
                'subject_type' => SupportCase::class,
                'subject_id' => $case->id,
                'user_id' => $actor->id,
                'action' => 'owner_changed',
                'description' => 'Case owner changed.',
                'properties' => [
                    'previous_owner_id' => $previousOwnerId,
                    'new_owner_id' => $newOwnerId,
                    'transfer_activities' => $transfer,
                ],
            ]);

            $case = $case->fresh(['owner']);

            if ($newOwnerId !== (int) $previousOwnerId && $case?->owner) {
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
        });
    }
}
