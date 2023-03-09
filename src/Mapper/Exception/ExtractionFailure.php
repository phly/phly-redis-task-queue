<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper\Exception;

use RuntimeException;

use function sprintf;

class ExtractionFailure extends RuntimeException implements ExceptionInterface
{
    public ?array $serialized = null;

    public static function forObject(object $object): self
    {
        return new self(sprintf(
            'Unable to extract object of type "%s"; no matching mapper',
            $object::class,
        ));
    }

    public static function forMissingTypeInSerialization(object $object, array $serialized): self
    {
        $instance             = new self(sprintf(
            'Invalid extraction for object of type "%s"; missing "__type" key',
            $object::class,
        ));
        $instance->serialized = $serialized;

        return $instance;
    }
}
