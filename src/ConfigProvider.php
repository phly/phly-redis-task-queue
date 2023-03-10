<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use Phly\ConfigFactory\ConfigFactory;
use React\EventLoop\LoopInterface;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'cron'             => $this->getCronConfig(),
            'dependencies'     => $this->getDependencyConfig(),
            'laminas-cli'      => $this->getConsoleConfig(),
            'redis-task-queue' => $this->getComponentConfig(),
        ];
    }

    public function getDependencyConfig(): array
    {
        return [
            'factories' => [
                'config-cron'                                => ConfigFactory::class,
                'config-redis-task-queue.mappers'            => ConfigFactory::class,
                Command\CronRunner::class                    => Command\CronRunnerFactory::class,
                Command\TaskRunner::class                    => Command\TaskRunnerFactory::class,
                Cron\Crontab::class                          => Cron\CrontabFactory::class,
                Cron\Dispatcher::class                       => Cron\DispatcherFactory::class,
                EventDispatcher\DeferredEventListener::class => EventDispatcher\DeferredEventListenerFactory::class,
                LoopInterface::class                         => LoopFactory::class,
                Mapper\Mapper::class                         => Mapper\MapperFactory::class,
                RedisTaskQueue::class                        => RedisTaskQueueFactory::class,
            ],
        ];
    }

    public function getComponentConfig(): array
    {
        return [
            'mappers' => [],
        ];
    }

    public function getConsoleConfig(): array
    {
        return [
            'commands' => [
                'phly:redis-task-queue:task-worker' => Command\TaskRunner::class,
                'phly:redis-task-queue:cron-runner' => Command\CronRunner::class,
            ],
        ];
    }

    public function getCronConfig(): array
    {
        return [
            'jobs' => [],
        ];
    }
}
