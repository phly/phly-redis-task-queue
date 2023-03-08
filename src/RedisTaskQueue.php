<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use Predis\Client;
use Predis\Response\ServerException;
use Psr\Log\LoggerInterface;

use function array_key_exists;
use function assert;
use function count;
use function is_array;
use function is_string;
use function json_encode;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class RedisTaskQueue
{
    public function __construct(
        private Client $redis,
        private readonly string $waitQueue = 'pending',
        private readonly string $workQueue = 'working',
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function queue(TaskInterface $task): void
    {
        $taskJson = $this->jsonEncode($task);
        $this->logger?->info('Queueing task: {task}', ['task' => $taskJson]);

        try {
            $this->redis->lpush($this->waitQueue, [$taskJson]);
        } catch (ServerException $e) {
            $this->logger?->error(
                'Error queueing task: {message}',
                ['message' => $e->getMessage()],
            );
            throw $e;
        }
    }

    public function hasPendingTasks(): bool
    {
        $tasks = $this->retrievePendingTasks();
        return count($tasks) > 0;
    }

    public function retrieveNextTask(): string
    {
        try {
            $task = $this->redis->rpoplpush($this->waitQueue, $this->workQueue);
        } catch (ServerException $e) {
            $this->logger?->error(
                'Error retrieving next task: {message}',
                ['message' => $e->getMessage()],
            );
            throw $e;
        }

        assert(is_string($task));

        return $task;
    }

    public function retrievePendingTasks(): array
    {
        try {
            $tasks = $this->redis->lrange($this->waitQueue, 0, -1);
        } catch (ServerException $e) {
            $this->logger?->error(
                'Error retrieving list of pending tasks: {message}',
                ['message' => $e->getMessage()],
            );
            throw $e;
        }

        return $tasks;
    }

    public function retrieveInProgressTasks(): array
    {
        try {
            $tasks = $this->redis->lrange($this->workQueue, 0, -1);
        } catch (ServerException $e) {
            $this->logger?->error(
                'Error retrieving list of in progress tasks: {message}',
                ['message' => $e->getMessage()],
            );
            throw $e;
        }

        return $tasks;
    }

    public function hasWorkingTasks(): bool
    {
        $tasks = $this->retrieveInProgressTasks();
        return count($tasks) > 0;
    }

    private function jsonEncode(TaskInterface $task): string
    {
        $data = $task->jsonSerialize();
        if (! is_array($data)) {
            throw Exception\InvalidSerialization::forTaskType($task::class);
        }

        if (! array_key_exists('__type', $data)) {
            throw Exception\InvalidSerialization::forMissingTaskType($task::class);
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
