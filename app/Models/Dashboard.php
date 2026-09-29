<?php

namespace App\Models;

use App\Models\Concerns\HasRecordUsers;
use Database\Factories\DashboardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dashboard extends Model
{
    /** @use HasFactory<DashboardFactory> */
    use HasFactory, HasRecordUsers;

    public const FOLDER_PRIVATE = 'private';

    public const MAX_WIDGETS = 20;

    /**
     * @var list<string>
     */
    public const WIDGET_TYPES = [
        'chart',
        'table',
        'metric',
        'gauge',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'folder',
        'widgets',
        'owner_id',
        'created_by',
        'updated_by',
    ];

    /**
     * @param  Builder<Dashboard>  $query
     * @return Builder<Dashboard>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role?->slug === 'admin') {
            return $query;
        }

        return $query->where('owner_id', $user->id);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'widgets' => 'array',
        ];
    }
}
