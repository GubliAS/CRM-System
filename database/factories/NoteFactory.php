<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notable_type' => Account::class,
            'notable_id' => Account::factory(),
            'body' => 'Demo note about Acme Industries.',
        ];
    }
}
