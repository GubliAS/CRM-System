<?php

namespace App\Actions\Opportunities;

use App\Models\Event;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CloneOpportunity
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, Opportunity $source, array $attributes): Opportunity
    {
        return DB::transaction(function () use ($actor, $source, $attributes): Opportunity {
            $clone = new Opportunity([
                'name' => $this->copiedName($source->name),
                'account_id' => $source->account_id,
                'amount' => $source->amount,
                'close_date' => $attributes['close_date'] ?? $source->close_date->toDateString(),
                'stage' => $source->stage,
                'type' => $source->type,
                'lead_source' => $source->lead_source,
                'next_step' => $source->next_step,
                'description' => $source->description,
                'owner_id' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'archived_at' => null,
            ]);
            $clone->save();
            $clone->recordStageHistory(null, $actor);

            if ($this->shouldCopyRelated($attributes)) {
                $this->copyRelated($actor, $source, $clone);
            }

            return $clone;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function shouldCopyRelated(array $attributes): bool
    {
        return filter_var($attributes['include_related'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function copiedName(string $name): string
    {
        $copied = 'Copy of '.$name;

        if (mb_strlen($copied) <= 120) {
            return $copied;
        }

        return mb_substr($copied, 0, 120);
    }

    private function copyRelated(User $actor, Opportunity $source, Opportunity $clone): void
    {
        $source->loadMissing(['notes', 'relatedTasks', 'relatedEvents']);

        foreach ($source->notes as $note) {
            Note::query()->create([
                'owner_id' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'notable_type' => Opportunity::class,
                'notable_id' => $clone->id,
                'body' => $note->body,
            ]);
        }

        foreach ($source->relatedTasks as $task) {
            Task::query()->create([
                'owner_id' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'subject' => $task->subject,
                'assigned_to_id' => $task->assigned_to_id,
                'related_type' => Opportunity::class,
                'related_id' => $clone->id,
                'contact_id' => $task->contact_id,
                'due_on' => $task->due_on,
                'status' => $task->status,
                'priority' => $task->priority,
                'comments' => $task->comments,
                'reminder_set' => $task->reminder_set,
                'reminder_at' => $task->reminder_at,
            ]);
        }

        foreach ($source->relatedEvents as $event) {
            Event::query()->create([
                'owner_id' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'subject' => $event->subject,
                'assigned_to_id' => $event->assigned_to_id,
                'related_type' => Opportunity::class,
                'related_id' => $clone->id,
                'contact_id' => $event->contact_id,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'all_day' => $event->all_day,
                'location' => $event->location,
                'show_time_as' => $event->show_time_as,
                'is_private' => $event->is_private,
                'description' => $event->description,
            ]);
        }
    }
}
