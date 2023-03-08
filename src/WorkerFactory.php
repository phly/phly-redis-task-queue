<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

use function assert;

final class WorkerFactory
{
    public function __invoke(ContainerInterface $container): Worker
    {
        $dispatcher = $container->get(EventDispatcherInterface::class);
        assert($dispatcher instanceof EventDispatcherInterface);

        $logger = $container->has(LoggerInterface::class)
            ? $container->get(LoggerInterface::class)
            : null;
        assert($logger instanceof LoggerInterface || null === $logger);

        return new Worker($dispatcher, $logger);
    }
}
