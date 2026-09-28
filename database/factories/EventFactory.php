<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $starts = now()->addDay()->setTime(10, 0);

        return [
            'subject' => 'Demo meeting with Northwind Supplies',
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'all_day' => false,
            'location' => 'Springfield conference room',
            'show_time_as' => 'Busy',
            'is_private' => false,
            'description' => 'Demo calendar event.',
        ];
    }
}
