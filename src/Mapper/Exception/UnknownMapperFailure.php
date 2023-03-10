<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper\Exception;

use RuntimeException;

class UnknownMapperFailure extends RuntimeException implements ExceptionInterface
{
    public ?string $serialized = null;

    public static function forHydration(string $serialized): self
    {
        $instance             = new self('Unable to hydrate object; no matching mapper');
        $instance->serialized = $serialized;

        return $instance;
    }
}
