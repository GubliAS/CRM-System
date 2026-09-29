<?php

namespace App\Actions\Opportunities;

use App\Models\Event;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Task;
use App\Models\User;
use App\Support\OpportunityStage;
use Illuminate\Support\Facades\DB;

class CloneOpportunity
{
    /**
     * @param  array{include_related?: bool}  $attributes
     */
    public function handle(User $actor, Opportunity $source, array $attributes = []): Opportunity
    {
        $includeRelated = (bool) ($attributes['include_related'] ?? false);

        return DB::transaction(function () use ($actor, $source, $includeRelated): Opportunity {
            $closeDate = $source->close_date;
            $today = now()->startOfDay();

            if ($closeDate === null || $closeDate->lt($today)) {
                $closeDate = $today;
            }

            $name = 'Copy of '.$source->name;
            if (strlen($name) > 120) {
                $name = substr($name, 0, 120);
            }

            $opportunity = Opportunity::query()->create([
                'name' => $name,
                'account_id' => $source->account_id,
                'amount' => $source->amount,
                'close_date' => $closeDate->toDateString(),
                'stage' => $source->stage,
                'type' => $source->type,
                'lead_source' => $source->lead_source,
                'next_step' => $source->next_step,
                'description' => $source->description,
                'owner_id' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            OpportunityStageHistory::query()->create([
                'opportunity_id' => $opportunity->id,
                'from_stage' => null,
                'to_stage' => $opportunity->stage,
                'probability' => OpportunityStage::probability($opportunity->stage),
                'user_id' => $actor->id,
            ]);

            if ($includeRelated) {
                $this->cloneRelated($actor, $source, $opportunity);
            }

            return $opportunity;
        });
    }

    private function cloneRelated(User $actor, Opportunity $source, Opportunity $copy): void
    {
        Task::query()
            ->where('related_type', Opportunity::class)
            ->where('related_id', $source->id)
            ->orderBy('id')
            ->each(function (Task $task) use ($actor, $copy): void {
                Task::query()->create([
                    'subject' => $task->subject,
                    'assigned_to_id' => $task->assigned_to_id,
                    'related_type' => Opportunity::class,
                    'related_id' => $copy->id,
                    'contact_id' => $task->contact_id,
                    'due_on' => $task->due_on,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'comments' => $task->comments,
                    'reminder_set' => $task->reminder_set,
                    'reminder_at' => $task->reminder_at,
                    'owner_id' => $actor->id,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
            });

        Event::query()
            ->where('related_type', Opportunity::class)
            ->where('related_id', $source->id)
            ->orderBy('id')
            ->each(function (Event $event) use ($actor, $copy): void {
                Event::query()->create([
                    'subject' => $event->subject,
                    'assigned_to_id' => $event->assigned_to_id,
                    'related_type' => Opportunity::class,
                    'related_id' => $copy->id,
                    'contact_id' => $event->contact_id,
                    'starts_at' => $event->starts_at,
                    'ends_at' => $event->ends_at,
                    'all_day' => $event->all_day,
                    'location' => $event->location,
                    'show_time_as' => $event->show_time_as,
                    'is_private' => $event->is_private,
                    'description' => $event->description,
                    'owner_id' => $actor->id,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
            });

        Note::query()
            ->where('notable_type', Opportunity::class)
            ->where('notable_id', $source->id)
            ->orderBy('id')
            ->each(function (Note $note) use ($actor, $copy): void {
                Note::query()->create([
                    'notable_type' => Opportunity::class,
                    'notable_id' => $copy->id,
                    'body' => $note->body,
                    'owner_id' => $actor->id,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
            });
    }
}
