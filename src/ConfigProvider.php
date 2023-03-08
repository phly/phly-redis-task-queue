<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use React\EventLoop\LoopInterface;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
            'laminas-cli'  => $this->getConsoleConfig(),
        ];
    }

    public function getDependencyConfig(): array
    {
        return [
            'factories' => [
                Command\TaskRunner::class                    => Command\TaskRunnerFactory::class,
                EventDispatcher\DeferredEventListener::class => EventDispatcher\DeferredEventListenerFactory::class,
                LoopInterface::class                         => LoopFactory::class,
                RedisTaskQueue::class                        => RedisTaskQueueFactory::class,
                Worker::class                                => WorkerFactory::class,
            ],
        ];
    }

    public function getConsoleConfig(): array
    {
        return [
            'commands' => [
                'phly:redis-task-queue:start' => Command\TaskRunner::class,
            ],
        ];
    }
}
