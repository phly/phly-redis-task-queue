<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper\Exception;

use RuntimeException;

use function Phly\RedisTaskQueue\jsonEncode;

class InvalidSerializationFailure extends RuntimeException implements ExceptionInterface
{
    public ?string $serialization = null;

    public static function forMissingType(array $serialized): self
    {
        $instance                = new self('Unable to hydrate object; serialization missing "__type" param');
        $instance->serialization = jsonEncode($serialized);

        return $instance;
    }
}
