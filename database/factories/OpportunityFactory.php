<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Opportunity;
use App\Support\OpportunityStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Acme Industries expansion',
            'account_id' => Account::factory(),
            'amount' => 10000,
            'close_date' => now()->addMonth()->toDateString(),
            'stage' => OpportunityStage::QUALIFICATION,
            'type' => 'New Business',
            'lead_source' => 'Web',
            'next_step' => 'Schedule a demo meeting',
            'description' => 'Demo opportunity.',
        ];
    }
}
