<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Command;

use Phly\RedisTaskQueue\Cron\Dispatcher;
use Psr\Container\ContainerInterface;
use React\EventLoop\LoopInterface;

use function array_filter;
use function assert;
use function in_array;
use function is_array;

final class CronRunnerFactory
{
    public function __invoke(ContainerInterface $container): CronRunner
    {
        $dispatcher = $container->get(Dispatcher::class);
        assert($dispatcher instanceof Dispatcher);

        $loop = $container->get(LoopInterface::class);
        assert($loop instanceof LoopInterface);

        $config = $container->get('config');
        assert(is_array($config));

        $config = $config['redis-task-queue'] ?? [];
        assert(is_array($config));

        $signalsToRegister = $config['signals'] ?? TaskRunner::DEFAULT_SIGNAL_LIST;
        assert(is_array($signalsToRegister));
        $signalsToRegister = array_filter(
            $signalsToRegister,
            fn (int $signal) => in_array($signal, CronRunner::DEFAULT_SIGNAL_LIST, true)
        );

        return new CronRunner($dispatcher, $loop, $signalsToRegister);
    }
}
