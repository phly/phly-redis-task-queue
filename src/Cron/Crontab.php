<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Cron;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

use function count;

/**
 * @template-implements IteratorAggregate<Cronjob>
 */
class Crontab implements Countable, IteratorAggregate
{
    /** @var Cronjob[] */
    private array $jobs = [];

    public function count(): int
    {
        return count($this->jobs);
    }

    /** @psalm-return Traversable<array-key, Cronjob> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->jobs);
    }

    public function append(Cronjob $job): void
    {
        $this->jobs[] = $job;
    }
}
