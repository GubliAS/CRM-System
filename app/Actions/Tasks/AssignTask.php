<?php

namespace App\Actions\Tasks;

use App\Mail\TaskAssignedMail;
use App\Models\Task;
use App\Models\User;
use App\Support\OutboundMail;

class AssignTask
{
    /**
     * @param  array{assigned_to_id: int}  $attributes
     */
    public function handle(User $actor, Task $task, array $attributes): Task
    {
        $previousAssigneeId = $task->assigned_to_id;
        $assignedToId = (int) $attributes['assigned_to_id'];

        $task->update([
            'assigned_to_id' => $assignedToId,
            'updated_by' => $actor->id,
        ]);

        $task = $task->fresh(['assignedTo']);

        if ($assignedToId !== (int) $previousAssigneeId && $task->assignedTo) {
            OutboundMail::queueAndLog(
                $actor,
                $task->assignedTo,
                $task,
                'Task assignment notification sent.',
                TaskAssignedMail::class,
                [$task, $task->assignedTo, $actor],
            );
        }

        return $task;
    }
}
