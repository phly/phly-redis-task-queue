<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use Phly\RedisTaskQueue\Mapper\Mapper;
use Predis\Client;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function assert;
use function is_array;
use function is_string;

final class RedisTaskQueueFactory
{
    public function __invoke(ContainerInterface $container): RedisTaskQueue
    {
        $config = $container->get('config');
        assert(is_array($config));

        $config = $config['redis-task-queue'] ?? [];
        assert(is_array($config));

        $waitQueue = $config['wait_queue'] ?? 'pending';
        assert(is_string($waitQueue));

        $workQueue = $config['work_queue'] ?? 'working';
        assert(is_string($workQueue));

        $mapper = $container->has(Mapper::class)
            ? $container->get(Mapper::class)
            : new Mapper();
        assert($mapper instanceof Mapper);

        $logger = $container->has(LoggerInterface::class)
            ? $container->get(LoggerInterface::class)
            : null;
        assert($logger instanceof LoggerInterface || null === $logger);

        $client = $container->get(Client::class);
        assert($client instanceof Client);

        return new RedisTaskQueue($client, $mapper, $waitQueue, $workQueue, $logger);
    }
}
