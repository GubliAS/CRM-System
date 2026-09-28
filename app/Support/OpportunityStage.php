<?php

namespace App\Support;

use InvalidArgumentException;

final class OpportunityStage
{
    public const QUALIFICATION = 'Qualification';

    public const MEETING_SCHEDULED = 'Meeting Scheduled';

    public const PROPOSAL = 'Proposal/Price Quote';

    public const NEGOTIATION = 'Negotiation/Review';

    public const CLOSED_WON = 'Closed Won';

    public const CLOSED_LOST = 'Closed Lost';

    /**
     * @var array<string, int>
     */
    public const PROBABILITIES = [
        self::QUALIFICATION => 10,
        self::MEETING_SCHEDULED => 20,
        self::PROPOSAL => 65,
        self::NEGOTIATION => 80,
        self::CLOSED_WON => 100,
        self::CLOSED_LOST => 0,
    ];

    public static function probability(string $stage): int
    {
        if (! array_key_exists($stage, self::PROBABILITIES)) {
            throw new InvalidArgumentException("Unknown opportunity stage [{$stage}].");
        }

        return self::PROBABILITIES[$stage];
    }

    public static function isClosed(string $stage): bool
    {
        return in_array($stage, [self::CLOSED_WON, self::CLOSED_LOST], true);
    }

    public static function isWon(string $stage): bool
    {
        return $stage === self::CLOSED_WON;
    }

    /**
     * @return list<array{name: string, probability: int}>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::PROBABILITIES as $name => $probability) {
            $options[] = [
                'name' => $name,
                'probability' => $probability,
            ];
        }

        return $options;
    }
}
