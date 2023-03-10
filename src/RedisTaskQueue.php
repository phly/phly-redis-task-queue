<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use Predis\Client;
use Predis\Response\ServerException;
use Psr\Log\LoggerInterface;

use function array_filter;
use function array_map;
use function assert;
use function count;
use function is_string;

final class RedisTaskQueue
{
    public function __construct(
        private Client $redis,
        private Mapper\Mapper $mapper,
        private readonly string $waitQueue = 'pending',
        private readonly string $workQueue = 'working',
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function queue(object $task): void
    {
        try {
            $taskJson = $this->mapper->toString($task);
        } catch (Mapper\Exception\ExtractionFailure $e) {
            $this->logger?->error('Unable to serialize task of type {task}: {message}', [
                'task'    => $task::class,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

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

    public function retrieveNextTask(): ?object
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

        if (null === $task) {
            return null;
        }

        assert(is_string($task));

        return $this->mapper->toObject($task);
    }

    /** @psalm-return list<object> */
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

        return $this->filterAndCastTaskList($tasks);
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

        return $this->filterAndCastTaskList($tasks);
    }

    public function hasWorkingTasks(): bool
    {
        $tasks = $this->retrieveInProgressTasks();
        return count($tasks) > 0;
    }

    /**
     * @psalm-param list<string> $tasks
     * @psalm-return list<object>
     */
    private function filterAndCastTaskList(array $tasks): array
    {
        $tasks = array_filter($tasks, function (string $task): bool {
            return $this->mapper->canCastToObject($task);
        });

        return array_map(function (string $task): object {
            return $this->mapper->toObject($task);
        }, $tasks);
    }
}
