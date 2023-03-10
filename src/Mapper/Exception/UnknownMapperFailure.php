<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper\Exception;

use RuntimeException;

use function Phly\RedisTaskQueue\jsonEncode;

class UnknownMapperFailure extends RuntimeException implements ExceptionInterface
{
    public ?string $serialized = null;

    public static function forHydration(array $serialized): self
    {
        $instance             = new self('Unable to hydrate object; no matching mapper');
        $instance->serialized = jsonEncode($serialized);

        return $instance;
    }
}
