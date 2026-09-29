<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\User;
use App\Support\OpportunityStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpportunityStageHistory>
 */
class OpportunityStageHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stage = OpportunityStage::QUALIFICATION;

        return [
            'opportunity_id' => Opportunity::factory(),
            'from_stage' => null,
            'to_stage' => $stage,
            'probability' => OpportunityStage::probability($stage),
            'user_id' => User::factory(),
        ];
    }
}
