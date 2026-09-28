<?php

namespace App\Rules;

use App\Support\OpportunityStage;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class MatchesOpportunityProbability implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $stage = request()->input('stage');

        if (! is_string($stage) || ! array_key_exists($stage, OpportunityStage::PROBABILITIES)) {
            return;
        }

        if (! is_numeric($value) || (float) $value !== (float) OpportunityStage::probability($stage)) {
            $fail('Probability is set by the stage.');
        }
    }
}
