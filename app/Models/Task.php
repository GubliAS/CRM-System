<?php

namespace App\Models;

use App\Models\Concerns\HasRecordUsers;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, HasRecordUsers;

    /**
     * @var list<class-string<Model>>
     */
    public const RELATED_MODELS = [
        Account::class,
        Contact::class,
        Lead::class,
        Opportunity::class,
        SupportCase::class,
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
        'due_on',
        'status',
        'priority',
        'comments',
        'reminder_set',
        'reminder_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (Task $task): void {
            $task->assertRelatedTypeAllowed();
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
            'due_on' => 'date',
            'reminder_set' => 'boolean',
            'reminder_at' => 'datetime',
        ];
    }

    private function assertRelatedTypeAllowed(): void
    {
        if ($this->related_type === null || $this->related_type === '') {
            return;
        }

        if (! in_array($this->related_type, self::RELATED_MODELS, true)) {
            throw new InvalidArgumentException('Tasks can only relate to an account, contact, lead, opportunity, or case.');
        }
    }
}
