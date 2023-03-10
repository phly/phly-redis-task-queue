<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Command;

use Phly\RedisTaskQueue\RedisTaskQueue;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use React\EventLoop\LoopInterface;

use function assert;
use function is_array;
use function is_numeric;

final class TaskRunnerFactory
{
    public function __invoke(ContainerInterface $container): TaskRunner
    {
        $queue = $container->get(RedisTaskQueue::class);
        assert($queue instanceof RedisTaskQueue);

        $dispatcher = $container->get(EventDispatcherInterface::class);
        assert($dispatcher instanceof EventDispatcherInterface);

        $loop = $container->get(LoopInterface::class);
        assert($loop instanceof LoopInterface);

        $config = $container->get('config');
        assert(is_array($config));

        $config = $config['redis-task-queue'] ?? [];
        assert(is_array($config));

        $interval = $config['task_runner_interval'] ?? 1.0;
        assert(is_numeric($interval));

        return new TaskRunner(
            $queue,
            $dispatcher,
            $loop,
            (float) $interval,
        );
    }
}
