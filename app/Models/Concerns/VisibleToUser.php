<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\RecordAccess;
use Illuminate\Database\Eloquent\Builder;

trait VisibleToUser
{
    /**
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        RecordAccess::constrain($query, $user, static::class);
    }
}
