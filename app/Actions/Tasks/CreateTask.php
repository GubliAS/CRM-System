<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\User;
use App\Support\RelatedRecords;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateTask
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, array $attributes): Task
    {
        return DB::transaction(function () use ($actor, $attributes): Task {
            $task = new Task($this->fields($attributes));
            $task->owner_id = $actor->id;
            $task->created_by = $actor->id;
            $task->updated_by = $actor->id;
            $task->save();

            return $task;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function fields(array $attributes): array
    {
        $relatedType = $attributes['related_type'] ?? null;
        $reminderSet = (bool) ($attributes['reminder_set'] ?? false);

        return [
            'subject' => $attributes['subject'],
            'assigned_to_id' => $attributes['assigned_to_id'],
            'related_type' => is_string($relatedType) ? (RelatedRecords::TASK_TYPES[$relatedType] ?? null) : null,
            'related_id' => $attributes['related_id'] ?? null,
            'contact_id' => $attributes['contact_id'] ?? null,
            'due_on' => $attributes['due_on'] ?? null,
            'status' => $attributes['status'] ?? 'Not Started',
            'priority' => $attributes['priority'] ?? null,
            'comments' => $attributes['comments'] ?? null,
            'reminder_set' => $reminderSet,
            'reminder_at' => $reminderSet
                ? Carbon::parse($attributes['reminder_date'].' '.$attributes['reminder_time'])
                : null,
        ];
    }
}
