<?php
/*
    Condorcet PHP - Election manager and results calculator.
    Designed for the Condorcet method. Integrating a large number of algorithms extending Condorcet. Expandable for all types of voting systems.

    By Julien Boudry and contributors - MIT LICENSE (Please read LICENSE.txt)
    https://github.com/julien-boudry/Condorcet
*/
declare(strict_types=1);

namespace Condorcet;

abstract class VoteConstraint
{
    public static function isVoteAllow (Election $election, Vote $vote) : bool
    {
        return static::evaluateVote($vote->getContextualRanking($election));
    }

    abstract protected static function evaluateVote (array $vote) : bool;
}