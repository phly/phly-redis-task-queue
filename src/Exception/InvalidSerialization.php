<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Exception;

use RuntimeException;

use function sprintf;

final class InvalidSerialization extends RuntimeException implements ExceptionInterface
{
    public static function forTaskType(string $taskType): self
    {
        return new self(sprintf(
            'Task of type %s serializes to an invalid representation; must be an object',
            $taskType,
        ));
    }

    public static function forMissingTaskType(string $taskType): self
    {
        return new self(sprintf(
            'Task of type %s serialization is missing __type property',
            $taskType,
        ));
    }
}
