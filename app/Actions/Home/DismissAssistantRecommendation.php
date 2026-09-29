<?php

namespace App\Actions\Home;

use App\Models\AssistantRecommendationDismissal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DismissAssistantRecommendation
{
    public function handle(User $actor, string $rule, Model $record): AssistantRecommendationDismissal
    {
        // Use ::class (not getMorphClass) so we never depend on a global morph map that
        // would rewrite related_type values for tasks/events elsewhere in the CRM.
        $type = AssistantRecommendationDismissal::canonicalizeRecommendableType($record::class);
        $id = $record->getKey();

        return DB::transaction(function () use ($actor, $rule, $type, $id): AssistantRecommendationDismissal {
            // Drop legacy FQCN rows so the unique key cannot diverge from the alias.
            AssistantRecommendationDismissal::query()
                ->where('user_id', $actor->id)
                ->where('rule', $rule)
                ->where('recommendable_id', $id)
                ->whereIn('recommendable_type', AssistantRecommendationDismissal::recommendableTypeKeys($type))
                ->where('recommendable_type', '!=', $type)
                ->delete();

            return AssistantRecommendationDismissal::query()->firstOrCreate(
                [
                    'user_id' => $actor->id,
                    'rule' => $rule,
                    'recommendable_type' => $type,
                    'recommendable_id' => $id,
                ],
            );
        });
    }
}
