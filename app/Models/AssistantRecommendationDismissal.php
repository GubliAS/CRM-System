<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssistantRecommendationDismissal extends Model
{
    public const RULE_INACTIVE_ACCOUNT = 'inactive_account';

    public const RULE_STALE_OPPORTUNITY = 'stale_opportunity';

    public const TYPE_ACCOUNT = Account::class;

    public const TYPE_OPPORTUNITY = Opportunity::class;

    /**
     * @var list<string>
     */
    public const RULES = [
        self::RULE_INACTIVE_ACCOUNT,
        self::RULE_STALE_OPPORTUNITY,
    ];

    /**
     * Canonical morph class names accepted on dismiss (and emitted in Home payloads).
     *
     * @var list<string>
     */
    public const RECOMMENDABLE_TYPES = [
        self::TYPE_ACCOUNT,
        self::TYPE_OPPORTUNITY,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'rule',
        'recommendable_type',
        'recommendable_id',
    ];

    /**
     * Canonical morph type for storage and comparison.
     */
    public static function canonicalizeRecommendableType(string $type): string
    {
        return match ($type) {
            'account', self::TYPE_ACCOUNT => self::TYPE_ACCOUNT,
            'opportunity', self::TYPE_OPPORTUNITY => self::TYPE_OPPORTUNITY,
            default => $type,
        };
    }

    /**
     * Canonical type plus legacy short slugs that may still exist in older rows.
     *
     * @return list<string>
     */
    public static function recommendableTypeKeys(string $type): array
    {
        return match (self::canonicalizeRecommendableType($type)) {
            self::TYPE_ACCOUNT => [self::TYPE_ACCOUNT, 'account'],
            self::TYPE_OPPORTUNITY => [self::TYPE_OPPORTUNITY, 'opportunity'],
            default => [$type],
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recommendable(): MorphTo
    {
        return $this->morphTo();
    }
}
