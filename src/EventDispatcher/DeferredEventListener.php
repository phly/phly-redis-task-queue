<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\EventDispatcher;

use Phly\RedisTaskQueue\RedisTaskQueue;
use Psr\Log\LoggerInterface;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class DeferredEventListener
{
    public function __construct(
        private readonly RedisTaskQueue $queue,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(DeferredEvent $event): void
    {
        $this->logger?->info(
            'Queuing task: {task}',
            [
                'task' => json_encode(
                    $event->wrappedEvent,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ),
            ]
        );
        $this->queue->queue($event->wrappedEvent);
    }
}
