<?php

namespace App\Models;

use App\Models\Concerns\HasRecordUsers;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasRecordUsers;

    /**
     * @var list<class-string<Model>>
     */
    public const RELATED_MODELS = [
        Account::class,
        Contact::class,
        Lead::class,
        Opportunity::class,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'created_by',
        'updated_by',
        'subject',
        'assigned_to_id',
        'related_type',
        'related_id',
        'contact_id',
        'starts_at',
        'ends_at',
        'all_day',
        'location',
        'show_time_as',
        'is_private',
        'description',
    ];

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            $event->assertRelatedTypeAllowed();
        });
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
            'is_private' => 'boolean',
        ];
    }

    private function assertRelatedTypeAllowed(): void
    {
        if ($this->related_type === null || $this->related_type === '') {
            return;
        }

        if (! in_array($this->related_type, self::RELATED_MODELS, true)) {
            throw new InvalidArgumentException('Events can only relate to an account, contact, lead, or opportunity.');
        }
    }
}
