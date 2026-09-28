<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RescheduleEvent
{
    public function handle(User $actor, Event $event, string $startsAt): Event
    {
        return DB::transaction(function () use ($actor, $event, $startsAt): Event {
            if ($event->all_day) {
                $origin = $event->starts_at->copy()->startOfDay();
                $target = Carbon::parse($startsAt)->startOfDay();
                $days = (int) round($origin->diffInDays($target));
                $event->starts_at = $event->starts_at->copy()->addDays($days);
                $event->ends_at = $event->ends_at->copy()->addDays($days);
            } else {
                $seconds = $event->ends_at->getTimestamp() - $event->starts_at->getTimestamp();
                $start = Carbon::parse($startsAt);
                $event->starts_at = $start;
                $event->ends_at = $start->copy()->addSeconds($seconds);
            }

            $event->updated_by = $actor->id;
            $event->save();

            return $event;
        });
    }
}
