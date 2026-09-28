<?php

namespace App\Actions\Search;

use App\Models\RecentlyViewedRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordRecentlyViewed
{
    public const KEEP_PER_TYPE = 25;

    public function handle(User $user, Model $record): void
    {
        RecentlyViewedRecord::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'viewable_type' => $record::class,
                'viewable_id' => $record->getKey(),
            ],
            [
                'viewed_at' => now(),
            ],
        );

        $keep = RecentlyViewedRecord::query()
            ->where('user_id', $user->id)
            ->where('viewable_type', $record::class)
            ->orderByDesc('viewed_at')
            ->limit(self::KEEP_PER_TYPE)
            ->pluck('id');

        RecentlyViewedRecord::query()
            ->where('user_id', $user->id)
            ->where('viewable_type', $record::class)
            ->whereNotIn('id', $keep)
            ->delete();
    }
}
