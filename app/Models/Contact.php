<?php

namespace App\Models;

use App\Models\Concerns\HasRecordUsers;
use App\Models\Concerns\VisibleToUser;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, HasRecordUsers, VisibleToUser;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'created_by',
        'updated_by',
        'account_id',
        'salutation',
        'first_name',
        'last_name',
        'title',
        'department',
        'phone',
        'mobile',
        'home_phone',
        'other_phone',
        'email',
        'fax',
        'reports_to_id',
        'assistant',
        'assistant_phone',
        'mailing_street',
        'mailing_city',
        'mailing_state',
        'mailing_postal_code',
        'mailing_country',
        'other_street',
        'other_city',
        'other_state',
        'other_postal_code',
        'other_country',
        'lead_source',
        'birthdate',
        'description',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reports_to_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'reports_to_id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(SupportCase::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function convertedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'converted_contact_id');
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
            'birthdate' => 'date',
        ];
    }
}
