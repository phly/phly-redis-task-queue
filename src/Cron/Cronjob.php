<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Cron;

class Cronjob
{
    public function __construct(
        public readonly string $schedule,
        public readonly string $task,
    ) {
    }
}
