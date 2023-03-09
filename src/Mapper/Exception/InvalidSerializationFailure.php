<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper\Exception;

use RuntimeException;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class InvalidSerializationFailure extends RuntimeException implements ExceptionInterface
{
    public ?string $serialization = null;

    public static function forMissingType(array $serialized): self
    {
        $instance                = new self('Unable to hydrate object; serialization missing "__type" param');
        $instance->serialization = json_encode($serialized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $instance;
    }
}
