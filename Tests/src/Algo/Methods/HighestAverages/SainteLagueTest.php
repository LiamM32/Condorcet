<?php

declare(strict_types=1);

namespace CondorcetPHP\Condorcet\Tests\Algo\Methods\HighestAverage;

use CondorcetPHP\Condorcet\Election;
use PHPUnit\Framework\TestCase;

class SainteLagueTest extends TestCase
{
    private readonly Election $election;

    protected function setUp(): void
    {
        $this->election = new Election;
    }

    protected function tearDown(): void
    {
        $this->election->setMethodOption('SainteLague', 'FirstDivisor', 1);
    }

    # https://fr.wikipedia.org/wiki/Scrutin_proportionnel_plurinominal#M%C3%A9thode_de_Sainte-Lagu%C3%AB
    public function testResult_1(): void
    {
        $this->election->addCandidate('A');
        $this->election->addCandidate('B');
        $this->election->addCandidate('C');

        $this->election->setNumberOfSeats(7);
        $this->election->allowsVoteWeight(true);

        $this->election->parseVotes('A ^53; B ^24; C ^23');

        self::assertSame('A > B > C > A > A > B > C', $this->election->getResult('SainteLague')->getResultAsString());

        self::assertSame([
            'Rounds' => [
                1 => [
                    'A' => [
                        'Quotient' => 53.0,
                        'NumberOfSeatsAllocatedBeforeRound' => 0,
                    ],
                    'B' => [
                        'Quotient' => 24.0,
                        'NumberOfSeatsAllocatedBeforeRound' => 0,
                    ],
                    'C' => [
                        'Quotient' => 23.0,
                        'NumberOfSeatsAllocatedBeforeRound' => 0,
                    ],
                ],
                2 => [
                    'A' => [
                        'Quotient' => 17.666666666666668,
                        'NumberOfSeatsAllocatedBeforeRound' => 1,
                    ],
                    'B' => [
                        'Quotient' => 24.0,
                        'NumberOfSeatsAllocatedBeforeRound' => 0,
                    ],
                    'C' => [
                        'Quotient' => 23.0,
                        'NumberOfSeatsAllocatedBeforeRound' => 0,
                    ],
                ],
                3 => [
                    'A' => [
                        'Quotient' => 17.666666666666668,
                        'NumberOfSeatsAllocatedBeforeRound' => 1,
                    ],
                    'B' => [
                        'Quotient' => 8.0,
                        'NumberOfSeatsAllocatedBeforeRound' => 1,
                    ],
                    'C' => [
                        'Quotient' => 23.0,
                        'NumberOfSeatsAllocatedBeforeRound' => 0,
                    ],
                ],
                4 => [
                    'A' => [
                        'Quotient' => 17.666666666666668,
                        'NumberOfSeatsAllocatedBeforeRound' => 1,
                    ],
                    'B' => [
                        'Quotient' => 8.0,
                        'NumberOfSeatsAllocatedBeforeRound' => 1,
                    ],
                    'C' => [
                        'Quotient' => 7.666666666666667,
                        'NumberOfSeatsAllocatedBeforeRound' => 1,
                    ],
                ],
                5 => [
                    'A' => [
                        'Quotient' => 10.6,
                        'NumberOfSeatsAllocatedBeforeRound' => 2,
                    ],
                    'B' => [
                        'Quotient' => 8.0,
                        'NumberOfSeatsAllocatedBeforeRound' => 1,
                    ],
                    'C' => [
                        'Quotient' => 7.666666666666667,
                        'NumberOfSeatsAllocatedBeforeRound' => 1,
                    ],
                ],
                6 => [
                    'A' => [
                        'Quotient' => 7.571428571428571,
                        'NumberOfSeatsAllocatedBeforeRound' => 3,
                    ],
                    'B' => [
                        'Quotient' => 8.0,
                        'NumberOfSeatsAllocatedBeforeRound' => 1,
                    ],
                    'C' => [
                        'Quotient' => 7.666666666666667,
                        'NumberOfSeatsAllocatedBeforeRound' => 1,
                    ],
                ],
                7 => [
                    'A' => [
                        'Quotient' => 7.571428571428571,
                        'NumberOfSeatsAllocatedBeforeRound' => 3,
                    ],
                    'B' => [
                        'Quotient' => 4.8,
                        'NumberOfSeatsAllocatedBeforeRound' => 2,
                    ],
                    'C' => [
                        'Quotient' => 7.666666666666667,
                        'NumberOfSeatsAllocatedBeforeRound' => 1,
                    ],
                ],
            ],
            'Seats per Candidates' => [
                'A' => 3,
                'B' => 2,
                'C' => 2,
            ],
        ], $this->election->getResult('SainteLague')->getStats());
    }

    # https://www.regjeringen.no/no/tema/valg-og-demokrati/den-norske-valgordningen/valgordningen/id456636/
    public function testNorwegianVariant_1(): void
    {
        $this->election->setMethodOption('SainteLague', 'FirstDivisor', 1.4);

        $this->election->parseCandidates('H;Ap;FrP;SV;SP;KrF');

        $this->election->setNumberOfSeats(11);
        $this->election->allowsVoteWeight(true);

        $this->election->parseVotes('H ^81140; Ap ^80862; FrP ^39851; SV ^26295; SP ^12187; KrF ^11229');

        self::assertSame('H > Ap > FrP > H > Ap > SV > H > Ap > FrP > H > Ap', $this->election->getResult('SainteLague')->getResultAsString());
    }
}
