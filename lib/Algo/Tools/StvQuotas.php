<?php
/*
    Condorcet PHP - Election manager and results calculator.
    Designed for the Condorcet method. Integrating a large number of algorithms extending Condorcet. Expandable for all types of voting systems.

    By Julien Boudry and contributors - MIT LICENSE (Please read LICENSE.txt)
    https://github.com/julien-boudry/Condorcet
*/
declare(strict_types=1);

/////////// TOOLS FOR MODULAR ALGORITHMS ///////////

namespace CondorcetPHP\Condorcet\Algo\Tools;

use CondorcetPHP\Condorcet\Throwable\StvQuotaNotImplementedException;

// Generic for Algorithms
Enum StvQuotas: string
{
    case DROOP = 'Droop Quota';
    case HARE = 'Hare Quota';
    case HAGENBACH_BISCHOFF = 'Hagenbach-Bischoff Quota';
    case IMPERIALI = 'Imperiali Quota';


    public static function make (string $quota): self
    {
        try {
            return match (strtolower($quota)) {
                'droop quota', 'droop' => self::DROOP,
                'hare quota', 'hare' => self::HARE,
                'hagenbach-bischoff quota', 'hagenbach-bischoff' => self::HAGENBACH_BISCHOFF,
                'imperiali quota', 'imperiali' => self::IMPERIALI,
            };
        } catch (\UnhandledMatchError $e) {
            throw new StvQuotaNotImplementedException('"'.$quota.'"');
        }
    }

    public function getQuota (int $votesWeight, int $seats): float
    {
        return match ($this) {
            self::DROOP => floor(( $votesWeight / ($seats + 1) ) + 1),
            self::HARE => $votesWeight / $seats,
            self::HAGENBACH_BISCHOFF => $votesWeight / ($seats + 1),
            self::IMPERIALI, => $votesWeight / ($seats+ 2),
        };
    }
}
