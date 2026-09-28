<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\PotentiallyTranslatedString;

class VisibleRelatedRecord implements ValidationRule
{
    /**
     * @param  array<string, class-string<Model>>  $allowed
     */
    public function __construct(private array $allowed) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $type = request()->input('related_type');

        if ($type === null || $type === '') {
            if ($value !== null && $value !== '') {
                $fail('Choose a related record type.');
            }

            return;
        }

        $class = $this->allowed[$type] ?? null;

        if (! is_string($class)) {
            $fail('The selected related record is not available.');

            return;
        }

        if ($value === null || $value === '') {
            $fail('Choose a related record.');

            return;
        }

        $user = request()->user();

        if ($user === null || ! $class::query()->visibleTo($user)->whereKey($value)->exists()) {
            $fail('The selected related record is not available.');
        }
    }
}
