<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final class Worker
{
    private TaskDecoder $decoder;

    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private ?LoggerInterface $logger = null,
    ) {
        $this->decoder = new TaskDecoder();
    }

    public function process(string $taskJson): void
    {
        $this->logger?->info('Processing task: {task}', ['task' => $taskJson]);
        $task = $this->decoder->decode($taskJson);
        $this->dispatcher->dispatch($task);
    }
}
