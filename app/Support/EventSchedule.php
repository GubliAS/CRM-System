<?php

namespace App\Support;

use Carbon\Carbon;

class EventSchedule
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function bounds(array $attributes): array
    {
        $allDay = (bool) ($attributes['all_day'] ?? false);

        if ($allDay) {
            return [
                Carbon::parse($attributes['starts_at'])->startOfDay(),
                Carbon::parse($attributes['ends_at'])->endOfDay(),
            ];
        }

        return [
            Carbon::parse($attributes['starts_at']),
            Carbon::parse($attributes['ends_at']),
        ];
    }
}
