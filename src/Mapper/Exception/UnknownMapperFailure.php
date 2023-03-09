<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper\Exception;

use RuntimeException;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class UnknownMapperFailure extends RuntimeException implements ExceptionInterface
{
    public ?string $serialized = null;

    public static function forHydration(array $serialized): self
    {
        $instance             = new self('Unable to hydrate object; no matching mapper');
        $instance->serialized = json_encode($serialized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $instance;
    }
}
