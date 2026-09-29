<?php

namespace App\Models;

use App\Models\Concerns\HasRecordUsers;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory, HasRecordUsers;

    public const FOLDER_PRIVATE = 'private';

    public const FOLDER_PUBLIC = 'public';

    public const OBJECT_LEAD = 'lead';

    public const OBJECT_OPPORTUNITY = 'opportunity';

    public const OBJECT_CASE = 'case';

    public const OBJECT_ACCOUNT = 'account';

    public const OBJECT_CONTACT = 'contact';

    /**
     * @var list<string>
     */
    public const FOLDERS = [
        self::FOLDER_PRIVATE,
        self::FOLDER_PUBLIC,
    ];

    /**
     * @var list<string>
     */
    public const OBJECT_TYPES = [
        self::OBJECT_LEAD,
        self::OBJECT_OPPORTUNITY,
        self::OBJECT_CASE,
        self::OBJECT_ACCOUNT,
        self::OBJECT_CONTACT,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'folder',
        'object_type',
        'columns',
        'filters',
        'group_by',
        'chart',
        'is_system',
        'system_key',
        'owner_id',
        'created_by',
        'updated_by',
    ];

    /**
     * @param  Builder<Report>  $query
     * @return Builder<Report>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role?->slug === 'admin') {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($user): void {
            $inner->where('folder', self::FOLDER_PUBLIC)
                ->orWhere(function (Builder $private) use ($user): void {
                    $private->where('folder', self::FOLDER_PRIVATE)
                        ->where('owner_id', $user->id);
                });
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'filters' => 'array',
            'group_by' => 'array',
            'chart' => 'array',
            'is_system' => 'boolean',
        ];
    }
}
