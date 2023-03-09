<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Cron;

use Phly\RedisTaskQueue\RedisTaskQueue;
use Psr\Container\ContainerInterface;

final class DispatcherFactory
{
    public function __invoke(ContainerInterface $container): Dispatcher
    {
        $queue = $container->get(RedisTaskQueue::class);
        assert($queue instanceof RedisTaskQueue);

        $crontab = $container->get(Crontab::class);
        assert($crontab instanceof Crontab);

        return new Dispatcher($queue, $crontab);
    }
}
