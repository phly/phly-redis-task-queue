<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper\Exception;

use RuntimeException;

use function sprintf;

class ExtractionFailure extends RuntimeException implements ExceptionInterface
{
    public static function forObject(object $object): self
    {
        return new self(sprintf(
            'Unable to extract object of type "%s"; no matching mapper',
            $object::class,
        ));
    }
}
