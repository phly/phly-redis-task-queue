<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use JsonException;
use Phly\RedisTaskQueue\Mapper\Mapper;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

use function Phly\RedisTaskQueue\jsonDecode;

final class Worker
{
    public function __construct(
        private Mapper $mapper,
        private EventDispatcherInterface $dispatcher,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function process(string $taskJson): void
    {
        $this->logger?->info('Processing task: {task}', ['task' => $taskJson]);

        $serialized = $this->jsonDecode($taskJson);
        $task       = $this->mapper->hydrate($serialized);

        $this->dispatcher->dispatch($task);
    }

    private function jsonDecode(string $taskJson): array
    {
        try {
            return jsonDecode($taskJson);
        } catch (JsonException $e) {
            $this->logger?->error(
                'Unable to deserialize task from JSON: {message}',
                ['message' => $e->getMessage()]
            );
            throw $e;
        }
    }
}
