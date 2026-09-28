<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RecordPasswordHistory
{
    public const KEEP = 5;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function changePassword(User $user, string $plainPassword, array $attributes = []): void
    {
        $this->store($user);
        $this->rejectIfReused($user, $plainPassword);

        $user->forceFill(array_merge($attributes, [
            'password' => $plainPassword,
        ]))->save();
    }

    public function rejectIfReused(User $user, string $plainPassword): void
    {
        $reused = $user->passwordHistories()
            ->latest('id')
            ->limit(self::KEEP)
            ->pluck('password')
            ->contains(fn (string $hash): bool => Hash::check($plainPassword, $hash));

        if ($reused) {
            throw ValidationException::withMessages([
                'password' => 'You cannot reuse one of your last 5 passwords.',
            ]);
        }
    }

    public function store(User $user): void
    {
        $hash = $user->password;

        if (! is_string($hash) || $hash === '') {
            return;
        }

        $latest = $user->passwordHistories()->latest('id')->value('password');

        if ($latest === $hash) {
            return;
        }

        $user->passwordHistories()->create([
            'password' => $hash,
        ]);

        $keep = $user->passwordHistories()
            ->latest('id')
            ->limit(self::KEEP)
            ->pluck('id');

        $user->passwordHistories()
            ->whereNotIn('id', $keep)
            ->delete();
    }
}
