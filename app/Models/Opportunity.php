<?php

namespace App\Models;

use App\Models\Concerns\HasRecordUsers;
use App\Models\Concerns\VisibleToUser;
use App\Support\OpportunityStage;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Opportunity extends Model
{
    /** @use HasFactory<OpportunityFactory> */
    use HasFactory, HasRecordUsers, VisibleToUser;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'created_by',
        'updated_by',
        'name',
        'account_id',
        'amount',
        'close_date',
        'stage',
        'probability',
        'type',
        'lead_source',
        'next_step',
        'description',
        'is_closed',
        'is_won',
        'archived_at',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'expected_revenue',
    ];

    protected static function booted(): void
    {
        static::saving(function (Opportunity $opportunity): void {
            $opportunity->syncStageFields();
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function convertedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'converted_opportunity_id');
    }

    public function relatedTasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'related');
    }

    public function relatedEvents(): MorphMany
    {
        return $this->morphMany(Event::class, 'related');
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
            'amount' => 'decimal:2',
            'close_date' => 'date',
            'probability' => 'integer',
            'is_closed' => 'boolean',
            'is_won' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    protected function expectedRevenue(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->amount === null) {
                return null;
            }

            return number_format(((float) $this->amount) * ((int) $this->probability) / 100, 2, '.', '');
        });
    }

    private function syncStageFields(): void
    {
        $stage = (string) $this->stage;

        $this->probability = OpportunityStage::probability($stage);
        $this->is_closed = OpportunityStage::isClosed($stage);
        $this->is_won = OpportunityStage::isWon($stage);
    }
}
