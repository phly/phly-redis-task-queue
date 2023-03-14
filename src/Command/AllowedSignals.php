<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Command;

use const SIGINT;
use const SIGKILL;
use const SIGTERM;

interface AllowedSignals
{
    /** @psalm-var list<int> */
    public const DEFAULT_SIGNAL_LIST = [SIGINT, SIGKILL, SIGTERM];
}
