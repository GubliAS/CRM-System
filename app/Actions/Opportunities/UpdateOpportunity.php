<?php

namespace App\Actions\Opportunities;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateOpportunity
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, Opportunity $opportunity, array $attributes): Opportunity
    {
        return DB::transaction(function () use ($actor, $opportunity, $attributes): Opportunity {
            $fromStage = $opportunity->stage;

            $opportunity->fill($this->fields($attributes));
            $opportunity->updated_by = $actor->id;
            $opportunity->save();

            if ($fromStage !== $opportunity->stage) {
                $opportunity->recordStageHistory($fromStage, $actor);
            }

            return $opportunity;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function fields(array $attributes): array
    {
        return [
            'name' => $attributes['name'],
            'account_id' => $attributes['account_id'],
            'amount' => $attributes['amount'] ?? null,
            'close_date' => $attributes['close_date'],
            'stage' => $attributes['stage'],
            'type' => $attributes['type'] ?? null,
            'lead_source' => $attributes['lead_source'] ?? null,
            'next_step' => $attributes['next_step'] ?? null,
            'description' => $attributes['description'] ?? null,
        ];
    }
}
