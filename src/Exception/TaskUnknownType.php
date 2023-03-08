<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Exception;

use Phly\RedisTaskQueue\TaskInterface;
use RuntimeException;

use function get_debug_type;
use function sprintf;

final class TaskUnknownType extends RuntimeException implements ExceptionInterface
{
    public static function forType(mixed $type): self
    {
        return new self(sprintf(
            'Task has an invalid __type property (not a class name): %s',
            get_debug_type($type),
        ));
    }

    public static function forClass(string $type): self
    {
        return new self(sprintf(
            'Task has a __type property that does not evaluate to a known class (%s)',
            $type,
        ));
    }

    public static function forNonTaskType(string $type): self
    {
        return new self(sprintf(
            'Task has a __type property that does not evaluate to a %s (%s)',
            TaskInterface::class,
            $type,
        ));
    }
}
