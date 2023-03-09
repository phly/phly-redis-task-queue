<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use JsonException;
use Phly\RedisTaskQueue\Mapper\Mapper;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

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
            $serialized = json_decode($taskJson, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logger?->error(
                'Unable to deserialize task from JSON: {message}',
                ['message' => $e->getMessage()]
            );
            throw $e;
        }

        if (! is_array($serialized)) {
            $this->logger?->error('Deserializing task from JSON does not result in array');
            throw new RuntimeException('Invalid task serialization');
        }

        return $serialized;
    }
}
