<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Cron;

use Phly\RedisTaskQueue\Mapper\Mapper;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function assert;
use function is_array;

final class CrontabFactory
{
    public function __invoke(ContainerInterface $container): Crontab
    {
        $config = $container->get('config-cron');
        assert(is_array($config));

        $logger = $container->get(LoggerInterface::class);
        assert($logger instanceof LoggerInterface);

        $jobs = $config['jobs'] ?? [];
        assert(is_array($jobs));

        $mapper = $container->has(Mapper::class)
            ? $container->get(Mapper::class)
            : new Mapper();
        assert($mapper instanceof Mapper);

        return (new ConfigParser($mapper))($jobs, $logger);
    }
}
