<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => 'Follow up with Acme Industries',
            'due_on' => now()->addWeek()->toDateString(),
            'status' => 'Not Started',
            'priority' => 'Normal',
            'comments' => 'Demo task.',
            'reminder_set' => false,
        ];
    }
}
