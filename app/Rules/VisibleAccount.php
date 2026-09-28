<?php

namespace App\Rules;

use App\Models\Account;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class VisibleAccount implements ValidationRule
{
    public function __construct(private ?int $exceptId = null) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($this->exceptId !== null && (int) $value === $this->exceptId) {
            $fail('An account cannot be its own parent.');

            return;
        }

        $user = request()->user();

        if ($user === null || ! Account::query()->visibleTo($user)->whereKey($value)->exists()) {
            $fail('The selected account is not available.');
        }
    }
}
