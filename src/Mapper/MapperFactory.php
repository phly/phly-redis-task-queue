<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper;

use Psr\Container\ContainerInterface;

use function assert;
use function is_array;
use function is_string;

final class MapperFactory
{
    public function __invoke(ContainerInterface $container): Mapper
    {
        $mappers     = new Mapper();
        $mappersList = $container->get('config-redis-task-queue.mappers');
        assert(is_array($mappersList));

        foreach ($mappersList as $mapper) {
            if (! is_string($mapper)) {
                continue;
            }

            if (! $container->has($mapper)) {
                continue;
            }

            $mapper = $container->get($mapper);
            if (! $mapper instanceof MapperInterface) {
                continue;
            }

            $mappers->attach($mapper);
        }

        return $mappers;
    }
}
