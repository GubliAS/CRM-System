<?php

namespace App\Support;

use App\Models\RecentlyViewedRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RecentlyViewed
{
    public const LIST_LIMIT = 5;

    /**
     * @param  class-string<Model>  $model
     * @return list<int|string>
     */
    public static function ids(User $user, string $model): array
    {
        return RecentlyViewedRecord::query()
            ->where('user_id', $user->id)
            ->where('viewable_type', $model)
            ->orderByDesc('viewed_at')
            ->limit(self::LIST_LIMIT)
            ->pluck('viewable_id')
            ->all();
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model>  $model
     * @param  list<int|string>  $ids
     */
    public static function constrainToIds(Builder $query, string $model, array $ids): void
    {
        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $column = (new $model)->getQualifiedKeyName();
        $query->whereIn($column, $ids);

        $cases = [];
        $bindings = [];

        foreach (array_values($ids) as $index => $id) {
            $cases[] = 'when ? then ?';
            $bindings[] = $id;
            $bindings[] = $index;
        }

        $query->orderByRaw('case '.$column.' '.implode(' ', $cases).' end', $bindings);
    }
}
