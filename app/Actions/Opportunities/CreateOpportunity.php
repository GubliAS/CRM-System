<?php

namespace App\Actions\Opportunities;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateOpportunity
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, array $attributes): Opportunity
    {
        return DB::transaction(function () use ($actor, $attributes): Opportunity {
            $opportunity = new Opportunity($this->fields($attributes));
            $opportunity->owner_id = $actor->id;
            $opportunity->created_by = $actor->id;
            $opportunity->updated_by = $actor->id;
            $opportunity->save();
            $opportunity->recordStageHistory(null, $actor);

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
