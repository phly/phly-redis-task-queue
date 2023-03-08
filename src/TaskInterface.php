<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use JsonSerializable;

/**
 * Implementations MUST, when serializing, include a property __type that
 * resolves to the class of the implementation.
 */
interface TaskInterface extends JsonSerializable
{
    public static function createFromStdClass(object $object): self;
}
