<?php
/*
    Part of Highest Averages Methods module - From the original Condorcet PHP

    Condorcet PHP - Election manager and results calculator.
    Designed for the Condorcet method. Integrating a large number of algorithms extending Condorcet. Expandable for all types of voting systems.

    By Julien Boudry and contributors - MIT LICENSE (Please read LICENSE.txt)
    https://github.com/julien-boudry/Condorcet
*/
declare(strict_types=1);

namespace CondorcetPHP\Condorcet\Algo\Methods\HighestAveragesMethod;

use CondorcetPHP\Condorcet\Algo\{Method, MethodInterface, StatsVerbosity};

# Copeland is a proportional algorithm | https://en.wikipedia.org/wiki/Webster/Sainte-Lagu%C3%AB_method
class SainteLague extends HighestAveragesMethod implements MethodInterface
{
    final public const IS_PROPORTIONAL = true;

    // Method Name
    public const METHOD_NAME = ['SainteLague'];

    protected function computeQuotient (int $candidateVotes, int $candidateSeats): float
    {
        return (float) ($candidateVotes / ($candidateSeats * 2 + 1));
    }
}
