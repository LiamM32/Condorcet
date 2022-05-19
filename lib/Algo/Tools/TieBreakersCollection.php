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

use CondorcetPHP\Condorcet\Dev\CondorcetDocumentationGenerator\CondorcetDocAttributes\{Description, Examples, FunctionReturn, PublicAPI, Related};
use CondorcetPHP\Condorcet\Election;
use CondorcetPHP\Condorcet\Algo\Tools\PairwiseStats;

// Generic for Algorithms
abstract class TieBreakersCollection
{
    public static function tieBreaker_1 (Election $election, array $candidatesKeys): array
    {
        $pairwise = $election->getPairwise();
        $pairwiseStats = PairwiseStats::PairwiseComparison($pairwise);
        $tooKeep = [];

        foreach ($candidatesKeys as $oneCandidateKeyTotest) :
            $select = true;
            foreach ($candidatesKeys as $oneChallengerKey) :
                if ($oneCandidateKeyTotest === $oneChallengerKey) :
                    continue;
                endif;

                if (    $pairwise[$oneCandidateKeyTotest]['win'][$oneChallengerKey] > $pairwise[$oneCandidateKeyTotest]['lose'][$oneChallengerKey] ||
                        $pairwiseStats[$oneCandidateKeyTotest]['balance'] > $pairwiseStats[$oneChallengerKey]['balance'] ||
                        $pairwiseStats[$oneCandidateKeyTotest]['win'] > $pairwiseStats[$oneChallengerKey]['win']
                ) :
                    $select = false;
                endif;
            endforeach;

            if ($select) :
                $tooKeep[] = $oneCandidateKeyTotest;
            endif;
        endforeach;

        return (\count($tooKeep) > 0) ? $tooKeep : $candidatesKeys;
    }

    public static function tieBreakerWithAnotherMethods (Election $election, array $methods, array $candidatesKeys): array
    {
        foreach ($methods as $oneMethod) :
            $tooKeep = [];

            $methodResults = $election->getResult($oneMethod)->getResultAsInternalKey();

            foreach ($methodResults as $rankKey => $rankValue) :
                foreach ($rankValue as $oneCandidateKey) :
                    if (\in_array($oneCandidateKey, $candidatesKeys, true)) :
                        $tooKeep[] = $oneCandidateKey;
                    endif;
                endforeach;

                if (\count($tooKeep) > 0 && \count($tooKeep) !== \count($candidatesKeys)) :
                    return $tooKeep;
                endif;
            endforeach;
        endforeach;

        return $candidatesKeys;
    }
}
