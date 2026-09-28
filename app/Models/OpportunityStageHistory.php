<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityStageHistory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'opportunity_id',
        'from_stage',
        'to_stage',
        'probability',
        'user_id',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'probability' => 'integer',
        ];
    }
}
