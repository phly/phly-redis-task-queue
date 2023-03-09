<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue\TestAsset;

class Task
{
    public function __construct(
        public readonly string $message,
    ) {
    }
}
