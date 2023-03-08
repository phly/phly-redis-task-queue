<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Exception;

use RuntimeException;

use function sprintf;

final class TaskMissingType extends RuntimeException implements ExceptionInterface
{
    public static function forJson(string $json): self
    {
        return new self(sprintf(
            'Task JSON representation is missing the __type property: %s',
            $json,
        ));
    }
}
