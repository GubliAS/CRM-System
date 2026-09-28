<?php

namespace App\Actions\Search;

use App\Models\SearchHistory;
use App\Models\User;

class RecordSearchHistory
{
    public const KEEP = 10;

    public function handle(User $user, string $query): void
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return;
        }

        $existing = SearchHistory::query()
            ->where('user_id', $user->id)
            ->where('query', $query)
            ->first();

        if ($existing) {
            $existing->touch();
        } else {
            SearchHistory::query()->create([
                'user_id' => $user->id,
                'query' => $query,
            ]);
        }

        $keep = SearchHistory::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->limit(self::KEEP)
            ->pluck('id');

        SearchHistory::query()
            ->where('user_id', $user->id)
            ->whereNotIn('id', $keep)
            ->delete();
    }
}
