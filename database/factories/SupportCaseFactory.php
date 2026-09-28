<?php

namespace Database\Factories;

use App\Models\SupportCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportCase>
 */
class SupportCaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => 'Question about an Acme Industries order',
            'description' => 'Demo case for a fictional company.',
            'status' => 'New',
            'priority' => 'Medium',
            'type' => 'Question',
            'origin' => 'Phone',
        ];
    }
}
