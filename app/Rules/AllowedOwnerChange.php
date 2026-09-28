<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AllowedOwnerChange implements ValidationRule
{
    public function __construct(private mixed $currentOwnerId) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ((int) $value === (int) $this->currentOwnerId) {
            return;
        }

        $user = request()->user();
        $user?->loadMissing('role');

        if (! in_array($user?->role?->slug, ['admin', 'sales-manager'], true)) {
            $fail('You cannot change the owner.');
        }
    }
}
