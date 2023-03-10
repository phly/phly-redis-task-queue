<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Cron;

use Phly\RedisTaskQueue\Mapper\Mapper;
use Phly\RedisTaskQueue\RedisTaskQueue;
use Psr\Container\ContainerInterface;

use function assert;

final class DispatcherFactory
{
    public function __invoke(ContainerInterface $container): Dispatcher
    {
        $queue = $container->get(RedisTaskQueue::class);
        assert($queue instanceof RedisTaskQueue);

        $crontab = $container->get(Crontab::class);
        assert($crontab instanceof Crontab);

        $mapper = $container->has(Mapper::class)
            ? $container->get(Mapper::class)
            : new Mapper();
        assert($mapper instanceof Mapper);

        return new Dispatcher($queue, $crontab, $mapper);
    }
}
