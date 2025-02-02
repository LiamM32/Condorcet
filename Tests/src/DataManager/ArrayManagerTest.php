<?php

declare(strict_types=1);

namespace CondorcetPHP\Condorcet\Tests\DataManager;

use CondorcetPHP\Condorcet\DataManager\ArrayManager;
use CondorcetPHP\Condorcet\{Election, Vote};
use PHPUnit\Framework\TestCase;

class ArrayManagerTest extends TestCase
{
    private readonly ArrayManager $ArrayManager;

    protected function setUp(): void
    {
        $this->ArrayManager = new class (new Election) extends ArrayManager {
            protected function preDeletedTask($object): void
            {
            }

            protected function decodeOneEntity(string $data): Vote
            {
                $vote = new Vote($data);
                $this->getElection()->checkVoteCandidate($vote);
                $vote->registerLink($this->Election->get());

                return $vote;
            }

            protected function encodeOneEntity(Vote $data): string
            {
                $data->destroyLink($this->getElection());

                return str_replace([' > ', ' = '], ['>', '='], (string) $data);
            }
        };
    }

    public function testOffsetSetAndOffetsetGet(): void
    {
        self::assertNull($this->ArrayManager->key());

        $this->ArrayManager[42] = 'foo';

        self::assertSame('foo', $this->ArrayManager[42]);

        self::assertNull($this->ArrayManager[43]);
    }
}
