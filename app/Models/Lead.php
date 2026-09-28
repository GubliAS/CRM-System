<?php

namespace App\Models;

use App\Models\Concerns\HasRecordUsers;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, HasRecordUsers;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'created_by',
        'updated_by',
        'salutation',
        'first_name',
        'last_name',
        'company',
        'title',
        'email',
        'phone',
        'mobile',
        'lead_status',
        'lead_source',
        'rating',
        'industry',
        'annual_revenue',
        'number_of_employees',
        'website',
        'street',
        'city',
        'state',
        'postal_code',
        'country',
        'description',
        'converted',
        'converted_account_id',
        'converted_contact_id',
        'converted_opportunity_id',
    ];

    public function convertedAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'converted_account_id');
    }

    public function convertedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'converted_contact_id');
    }

    public function convertedOpportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'converted_opportunity_id');
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
            'annual_revenue' => 'decimal:2',
            'number_of_employees' => 'integer',
            'converted' => 'boolean',
        ];
    }
}
