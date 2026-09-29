<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssistantRecommendationDismissal extends Model
{
    public const RULE_INACTIVE_ACCOUNT = 'inactive_account';

    public const RULE_STALE_OPPORTUNITY = 'stale_opportunity';

    /**
     * Stable morph aliases used on the wire and in storage (no PHP namespace backslashes).
     */
    public const TYPE_ACCOUNT = 'account';

    public const TYPE_OPPORTUNITY = 'opportunity';

    /**
     * @var list<string>
     */
    public const RULES = [
        self::RULE_INACTIVE_ACCOUNT,
        self::RULE_STALE_OPPORTUNITY,
    ];

    /**
     * Accepted dismiss payload types: short aliases plus legacy FQCN values.
     *
     * @var list<string>
     */
    public const RECOMMENDABLE_TYPES = [
        self::TYPE_ACCOUNT,
        self::TYPE_OPPORTUNITY,
        Account::class,
        Opportunity::class,
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
     * Canonical morph alias for storage, Home payloads, and comparison.
     */
    public static function canonicalizeRecommendableType(string $type): string
    {
        return match ($type) {
            'account', Account::class => self::TYPE_ACCOUNT,
            'opportunity', Opportunity::class => self::TYPE_OPPORTUNITY,
            default => $type,
        };
    }

    /**
     * Canonical alias plus legacy FQCN rows that may still exist.
     *
     * @return list<string>
     */
    public static function recommendableTypeKeys(string $type): array
    {
        return match (self::canonicalizeRecommendableType($type)) {
            self::TYPE_ACCOUNT => [self::TYPE_ACCOUNT, Account::class],
            self::TYPE_OPPORTUNITY => [self::TYPE_OPPORTUNITY, Opportunity::class],
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
