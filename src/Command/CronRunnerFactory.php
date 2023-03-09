<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Command;

use Phly\RedisTaskQueue\Cron\Dispatcher;
use Psr\Container\ContainerInterface;
use React\EventLoop\LoopInterface;

use function assert;

final class CronRunnerFactory
{
    public function __invoke(ContainerInterface $container): CronRunner
    {
        $dispatcher = $container->get(Dispatcher::class);
        assert($dispatcher instanceof Dispatcher);

        $loop = $container->get(LoopInterface::class);
        assert($loop instanceof LoopInterface);

        return new CronRunner($dispatcher, $loop);
    }
}
