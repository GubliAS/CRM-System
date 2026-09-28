<?php

namespace App\Models;

use App\Models\Concerns\HasRecordUsers;
use App\Models\Concerns\VisibleToUser;
use Database\Factories\SupportCaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupportCase extends Model
{
    /** @use HasFactory<SupportCaseFactory> */
    use HasFactory, HasRecordUsers, VisibleToUser;

    public const STATUS_CLOSED = 'Closed';

    public const NUMBER_OFFSET = 100000;

    protected $table = 'cases';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'created_by',
        'updated_by',
        'case_number',
        'contact_id',
        'account_id',
        'subject',
        'description',
        'internal_comments',
        'status',
        'priority',
        'type',
        'origin',
        'reason',
        'web_email',
        'web_name',
        'web_company',
        'web_phone',
        'is_closed',
        'closed_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (SupportCase $case): void {
            $case->syncClosureFields();
        });

        static::created(function (SupportCase $case): void {
            if (filled($case->case_number)) {
                return;
            }

            $case->case_number = self::numberFromId($case->id);
            $case->saveQuietly();
        });
    }

    public static function numberFromId(int $id): string
    {
        return 'C-'.(self::NUMBER_OFFSET + $id);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function relatedTasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'related');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_closed' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    private function syncClosureFields(): void
    {
        if ($this->status === self::STATUS_CLOSED) {
            $this->is_closed = true;
            $this->closed_at ??= now();

            return;
        }

        $this->is_closed = false;
        $this->closed_at = null;
    }
}
