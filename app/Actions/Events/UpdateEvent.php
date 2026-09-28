<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\User;
use App\Support\EventSchedule;
use App\Support\RelatedRecords;
use Illuminate\Support\Facades\DB;

class UpdateEvent
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, Event $event, array $attributes): Event
    {
        return DB::transaction(function () use ($actor, $event, $attributes): Event {
            [$startsAt, $endsAt] = EventSchedule::bounds($attributes);
            $relatedType = $attributes['related_type'] ?? null;

            $event->fill([
                'subject' => $attributes['subject'],
                'assigned_to_id' => $attributes['assigned_to_id'],
                'related_type' => is_string($relatedType) ? (RelatedRecords::EVENT_TYPES[$relatedType] ?? null) : null,
                'related_id' => $attributes['related_id'] ?? null,
                'contact_id' => $attributes['contact_id'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'all_day' => (bool) ($attributes['all_day'] ?? false),
                'location' => $attributes['location'] ?? null,
                'show_time_as' => $attributes['show_time_as'] ?? 'Busy',
                'is_private' => (bool) ($attributes['is_private'] ?? false),
                'description' => $attributes['description'] ?? null,
            ]);
            $event->updated_by = $actor->id;
            $event->save();

            return $event;
        });
    }
}
