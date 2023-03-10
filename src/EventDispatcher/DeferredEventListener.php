<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\EventDispatcher;

use Phly\RedisTaskQueue\RedisTaskQueue;
use Psr\Log\LoggerInterface;

use function Phly\RedisTaskQueue\jsonEncode;

final class DeferredEventListener
{
    public function __construct(
        private readonly RedisTaskQueue $queue,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(DeferredEvent $event): void
    {
        $this->logger?->info('Queuing task: {task}', [
            'task' => jsonEncode($event->wrappedEvent),
        ]);
        $this->queue->queue($event->wrappedEvent);
    }
}
