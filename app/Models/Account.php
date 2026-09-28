<?php

namespace App\Models;

use App\Models\Concerns\HasRecordUsers;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, HasRecordUsers;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'created_by',
        'updated_by',
        'name',
        'parent_account_id',
        'phone',
        'fax',
        'website',
        'type',
        'industry',
        'employees',
        'annual_revenue',
        'billing_street',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'shipping_street',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'description',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_account_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(SupportCase::class);
    }

    public function convertedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'converted_account_id');
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
            'employees' => 'integer',
            'annual_revenue' => 'decimal:2',
        ];
    }
}
