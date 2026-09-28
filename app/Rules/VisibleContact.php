<?php

namespace App\Rules;

use App\Models\Contact;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class VisibleContact implements ValidationRule
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
            $fail('A contact cannot report to itself.');

            return;
        }

        $user = request()->user();

        if ($user === null || ! Contact::query()->visibleTo($user)->whereKey($value)->exists()) {
            $fail('The selected contact is not available.');
        }
    }
}
