<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Command;

use Phly\RedisTaskQueue\RedisTaskQueue;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use React\EventLoop\LoopInterface;

use function array_filter;
use function assert;
use function in_array;
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

        $signalsToRegister = $config['signals'] ?? TaskRunner::DEFAULT_SIGNAL_LIST;
        assert(is_array($signalsToRegister));
        $signalsToRegister = array_filter(
            $signalsToRegister,
            fn (int $signal) => in_array($signal, TaskRunner::DEFAULT_SIGNAL_LIST, true)
        );

        return new TaskRunner(
            $queue,
            $dispatcher,
            $loop,
            (float) $interval,
            $signalsToRegister,
        );
    }
}
