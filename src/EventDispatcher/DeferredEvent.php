<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\EventDispatcher;

use Phly\RedisTaskQueue\TaskInterface;

final class DeferredEvent
{
    public function __construct(
        public readonly TaskInterface $wrappedEvent,
    ) {
    }
}
