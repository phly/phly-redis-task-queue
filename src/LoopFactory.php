<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

final class LoopFactory
{
    public function __invoke(): LoopInterface
    {
        return Loop::get();
    }
}
