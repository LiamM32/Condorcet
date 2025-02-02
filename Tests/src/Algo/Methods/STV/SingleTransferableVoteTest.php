<?php

declare(strict_types=1);

namespace CondorcetPHP\Condorcet\Tests\Algo\STV;

use CondorcetPHP\Condorcet\Election;
use CondorcetPHP\Condorcet\Throwable\StvQuotaNotImplementedException;
use CondorcetPHP\Condorcet\Algo\Methods\STV\SingleTransferableVote;
use CondorcetPHP\Condorcet\Algo\StatsVerbosity;
use CondorcetPHP\Condorcet\Algo\Tools\StvQuotas;
use PHPUnit\Framework\TestCase;

class SingleTransferableVoteTest extends TestCase
{
    private readonly Election $election;

    protected function setUp(): void
    {
        $this->election = new Election;
    }

    protected function tearDown(): void
    {
        $this->election->setMethodOption('STV', 'Quota', StvQuotas::DROOP);
    }

    public function testQuotaOption(): never
    {
        self::assertSame(StvQuotas::DROOP, StvQuotas::make('droop'));

        self::assertTrue(
            $this->election->setMethodOption('STV', 'Quota', StvQuotas::make('Hagenbach-Bischoff'))
        );

        $this->expectException(StvQuotaNotImplementedException::class);
        $this->expectExceptionMessage('This STV quota is not implemented: "another quota"');

        $this->election->setMethodOption('STV', 'Quota', StvQuotas::make('another quota'));
    }

    public function testResult_1(): void
    {
        # From https://fr.wikipedia.org/wiki/Scrutin_%C3%A0_vote_unique_transf%C3%A9rable

        $this->election->addCandidate('D');
        $this->election->addCandidate('B');
        $this->election->addCandidate('C');
        $this->election->addCandidate('A');

        $this->election->allowsVoteWeight(true);

        $this->election->parseVotes('
            A>B>C>D ^ 28
            A>C>D>B ^ 14
            B>C>A>D ^ 15
            C>A>B>D ^ 17
            D>B>C>A ^ 26
        ');

        $this->election->setNumberOfSeats(2);


        self::assertEqualsWithDelta(
            [
                1 => [
                    'A' => 42.0,
                    'D' => 26.0,
                    'C' => 17.0,
                    'B' => 15.0,
                ],
                2 => [
                    'D' => 26.0,
                    'B' => 20.33333333333,
                    'C' => 19.66666666667,
                ],
                3 => [
                    'B' => 37.33333333333,
                    'D' => 28.66666666667,
                ],
            ],
            $this->election->getResult('STV')->getStats()['rounds'],
            1 / (0.1 ** SingleTransferableVote::DECIMAL_PRECISION)
        );

        self::assertSame(
            (float) 34,
            $this->election->getResult('STV')->getStats()['Votes Needed to Win']
        );

        self::assertSame(
            [
                1 => 'A',
                2 => 'B',
            ],
            $this->election->getResult('STV')->getResultAsArray(true)
        );
    }

    public function testResult_2(): void
    {
        # From https://en.wikipedia.org/wiki/Single_transferable_vote

        $this->election->addCandidate('Orange');
        $this->election->addCandidate('Pear');
        $this->election->addCandidate('Chocolate');
        $this->election->addCandidate('Strawberry');
        $this->election->addCandidate('Hamburger');

        $this->election->setImplicitRanking(false);
        $this->election->allowsVoteWeight(true);

        $this->election->setNumberOfSeats(3);


        $this->election->parseVotes('
            Orange ^ 4
            Pear > Orange * 2
            Chocolate > Strawberry * 8
            Chocolate > Hamburger * 4
            Strawberry
            Hamburger
        ');

        self::assertSame(
            (float) 6,
            $this->election->getResult('STV')->getStats()['Votes Needed to Win']
        );

        self::assertSame(
            [
                1 => [
                    'Chocolate' => 12.0,
                    'Orange' => 4.0,
                    'Pear' => 2.0,
                    'Strawberry' => 1.0,
                    'Hamburger' => 1.0,
                ],
                2 => [
                    'Strawberry' => 5.0,
                    'Orange' => 4.0,
                    'Hamburger' => 3.0,
                    'Pear' => 2.0,
                ],
                3 => [
                    'Orange' => 6.0,
                    'Strawberry' => 5.0,
                    'Hamburger' => 3.0,
                ],
                4 => [
                    'Strawberry' => 5.0,
                    'Hamburger' => 3.0,
                ],
                5 => [
                    'Strawberry' => 5.0,
                ],
            ],
            $this->election->getResult('STV')->getStats()['rounds']
        );

        self::assertSame(
            [
                1 => 'Chocolate',
                2 => 'Orange',
                3 => 'Strawberry',
            ],
            $this->election->getResult('STV')->getResultAsArray(true)
        );
    }

    public function testResult_3(): void
    {
        # From https://en.wikipedia.org/wiki/Schulze_STV

        $this->election->addCandidate('Andrea');
        $this->election->addCandidate('Brad');
        $this->election->addCandidate('Carter');

        $this->election->setImplicitRanking(false);
        $this->election->allowsVoteWeight(true);

        $this->election->setNumberOfSeats(2);

        $this->election->parseVotes('
            Andrea > Brad > Carter ^ 12
            Andrea > Carter > Brad ^ 26
            Andrea > Carter > Brad ^ 12
            Carter > Andrea > Brad ^ 13
            Brad ^ 27
        ');

        self::assertSame(
            (float) 31,
            $this->election->getResult('STV')->getStats()['Votes Needed to Win']
        );

        self::assertSame(
            [
                1 => [
                    'Andrea' => 50.0,
                    'Brad' => 27.0,
                    'Carter' => 13.0,
                ],
                2 => [
                    'Brad' => 31.56,
                    'Carter' => 27.44,
                ],
            ],
            $this->election->getResult('STV')->getStats()['rounds']
        );

        self::assertSame(
            [
                1 => 'Andrea',
                2 => 'Brad',
            ],
            $this->election->getResult('STV')->getResultAsArray(true)
        );

        $this->election->setStatsVerbosity(StatsVerbosity::LOW);
        self::assertArrayNotHasKey('rounds', $this->election->getResult('STV')->getStats());
    }

    public function testResult_4(): void
    {
        # From https://it.wikipedia.org/wiki/Voto_singolo_trasferibile

        $this->election->addCandidate('D');
        $this->election->addCandidate('B');
        $this->election->addCandidate('C');
        $this->election->addCandidate('A');

        $this->election->allowsVoteWeight(true);

        $this->election->parseVotes('
            A>D ^ 40
            B>A ^ 10
            B>C ^ 5
            C>B ^ 25
            D>B ^ 20
        ');

        $this->election->setNumberOfSeats(3);

        self::assertSame(
            (float) 26,
            $this->election->getResult('STV')->getStats()['Votes Needed to Win']
        );

        self::assertSame(
            [
                1 => 'A',
                2 => 'D',
                3 => 'C',
            ],
            $this->election->getResult('STV')->getResultAsArray(true)
        );
    }


    public function testResult_AlternativeQuotas1(): void
    {
        # From https://en.wikipedia.org/wiki/Hagenbach-Bischoff_quota

        $this->election->addCandidate('Andrea');
        $this->election->addCandidate('Carter');
        $this->election->addCandidate('Brad');

        $this->election->setImplicitRanking(false);
        $this->election->allowsVoteWeight(true);

        $this->election->parseVotes('
            Andrea > Carter ^45
            Carter ^25
            Brad ^30
        ');

        $this->election->setNumberOfSeats(2);
        $this->election->setMethodOption('STV', 'Quota', StvQuotas::make('Hagenbach-Bischoff'));

        self::assertSame(
            round(33 + 1/3, SingleTransferableVote::DECIMAL_PRECISION, \PHP_ROUND_HALF_DOWN),
            $this->election->getResult('STV')->getStats()['Votes Needed to Win']
        );

        self::assertEqualsWithDelta(
            [
                1 => [
                    'Andrea' => 45.0,
                    'Brad' => 30.0,
                    'Carter' => 25.0,
                ],
                2 => [
                    'Carter' => 36.0 + 2/3,
                    'Brad' => 30.0,
                ],
            ],
            $this->election->getResult('STV')->getStats()['rounds'],
            delta: 1 / (0.1 ** SingleTransferableVote::DECIMAL_PRECISION)
        );

        self::assertSame(
            [
                1 => 'Andrea',
                2 => 'Carter',
            ],
            $this->election->getResult('STV')->getResultAsArray(true)
        );

        self::assertsame($this->election->getResult('STV')->getMethodOptions()['Quota'], StvQuotas::make('Hagenbach-Bischoff'));
    }

    public function testResult_AlternativeQuotas2(): void
    {
        # From https://en.wikipedia.org/wiki/Imperiali_quota

        $this->election->addCandidate('Andrea');
        $this->election->addCandidate('Carter');
        $this->election->addCandidate('Brad');

        $this->election->setImplicitRanking(false);
        $this->election->allowsVoteWeight(true);

        $this->election->parseVotes('
            Andrea > Carter ^65
            Carter ^15
            Brad ^20
        ');

        $this->election->setNumberOfSeats(2);
        $this->election->setMethodOption('STV', 'Quota', StvQuotas::IMPERIALI);

        self::assertSame(
            (float) (100 / (2 + 2)),
            $this->election->getResult('STV')->getStats()['Votes Needed to Win']
        );

        self::assertSame(
            [
                1 => [
                    'Andrea' => 65.0,
                    'Brad' => 20.0,
                    'Carter' => 15.0,
                ],
                2 => [
                    'Carter' => 55.0,
                    'Brad' => 20.0,
                ],
            ],
            $this->election->getResult('STV')->getStats()['rounds']
        );

        self::assertSame(
            [
                1 => 'Andrea',
                2 => 'Carter',
            ],
            $this->election->getResult('STV')->getResultAsArray(true)
        );

        self::assertsame($this->election->getResult('STV')->getMethodOptions()['Quota'], StvQuotas::make('Imperiali quota'));
    }

    public function testResult_AlternativeQuotas3(): void
    {
        # From https://en.wikipedia.org/wiki/Hare_quota

        $this->election->addCandidate('Andrea');
        $this->election->addCandidate('Carter');
        $this->election->addCandidate('Brad');

        $this->election->setImplicitRanking(false);
        $this->election->allowsVoteWeight(true);

        $this->election->parseVotes('
            Andrea > Carter ^60
            Carter ^14
            Brad ^26
        ');

        $this->election->setNumberOfSeats(2);
        $this->election->setMethodOption('STV', 'Quota', StvQuotas::make('Hare quota'));

        self::assertSame(
            (float) (100 / 2),
            $this->election->getResult('STV')->getStats()['Votes Needed to Win']
        );

        self::assertSame(
            [
                1 => [
                    'Andrea' => 60.0,
                    'Brad' => 26.0,
                    'Carter' => 14.0,
                ],
                2 => [
                    'Brad' => 26.0,
                    'Carter' => 24.0,
                ],
                3 => ['Brad' => 26.0],
            ],
            $this->election->getResult('STV')->getStats()['rounds']
        );

        self::assertSame(
            [
                1 => 'Andrea',
                2 => 'Brad',
            ],
            $this->election->getResult('STV')->getResultAsArray(true)
        );

        self::assertsame($this->election->getResult('STV')->getMethodOptions()['Quota'], StvQuotas::HARE);
    }

    public function testResult_AlternativeQuotas4(): void
    {
        # From https://en.wikipedia.org/wiki/CPO-STV

        $this->election->addCandidate('Andrea');
        $this->election->addCandidate('Carter');
        $this->election->addCandidate('Brad');
        $this->election->addCandidate('Delilah');
        $this->election->addCandidate('Scott');

        $this->election->setImplicitRanking(false);
        $this->election->allowsVoteWeight(true);

        $this->election->parseVotes('
            Andrea ^25
            Carter > Brad > Delilah ^34
            Brad > Delilah ^7
            Delilah > Brad ^8
            Delilah > Scott ^5
            Scott > Delilah ^21
        ');

        $this->election->setNumberOfSeats(3);
        $this->election->setMethodOption('STV', 'Quota', StvQuotas::HAGENBACH_BISCHOFF);

        self::assertSame(
            (float) 25,
            $this->election->getResult('STV')->getStats()['Votes Needed to Win']
        );

        self::assertSame(
            [
                1 => [
                    'Carter' => 34.0,
                    'Andrea' => 25.0,
                    'Scott' => 21.0,
                    'Delilah' => 13.0,
                    'Brad' => 7.0,
                ],
                2 => [
                    'Scott' => 21.0,
                    'Brad' => 16.0,
                    'Delilah' => 13.0,
                ],
                3 => [
                    'Scott' => 26.0,
                    'Brad' => 24.0,
                ],
            ],
            $this->election->getResult('STV')->getStats()['rounds']
        );

        self::assertSame(
            [
                1 => 'Carter',
                2 => 'Andrea',
                3 => 'Scott',
            ],
            $this->election->getResult('STV')->getResultAsArray(true)
        );
    }
}
