<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue;

use Phly\RedisTaskQueue\RedisTaskQueue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class RedisTaskQueueTest extends TestCase
{
    protected static function jsonEncode(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function testQueuingPushesToRedis(): void
    {
        $task  = new TestAsset\Task('Task message');

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lpush'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lpush')
            ->with('pending', [self::jsonEncode($task)]);

        $queue = new RedisTaskQueue($redis);

        $this->assertNull($queue->queue($task));
    }

    public function testRetrievePendingTasksPullsListFromWaitQueue(): void
    {
        $tasks = [
            'one',
            'two',
            'three',
        ];

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lrange'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lrange')
            ->with('pending', 0, -1)
            ->willReturn($tasks);

        $queue = new RedisTaskQueue($redis);

        $this->assertSame($tasks, $queue->retrievePendingTasks());
    }

    public function testHasPendingTasksReturnsFalseWhenNoTasksInWaitQueue(): void
    {
        $tasks = [];

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lrange'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lrange')
            ->with('pending', 0, -1)
            ->willReturn($tasks);

        $queue = new RedisTaskQueue($redis);

        $this->assertFalse($queue->hasPendingTasks());
    }

    public function testHasPendingTasksReturnsTrueWhenOneOrMoreTasksInWaitQueue(): void
    {
        $tasks = [
            'one',
            'two',
            'three',
        ];

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lrange'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lrange')
            ->with('pending', 0, -1)
            ->willReturn($tasks);

        $queue = new RedisTaskQueue($redis);

        $this->assertTrue($queue->hasPendingTasks());
    }

    public function testRetrieveNextTaskPopsTaskFromWaitQueueAndPushesToWorkQueue(): void
    {
        $task = 'task';

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['rpoplpush'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('rpoplpush')
            ->with('pending', 'working')
            ->willReturn($task);

        $queue = new RedisTaskQueue($redis);

        $this->assertSame($task, $queue->retrieveNextTask());
    }

    public function testRetrieveInProgressTasksPullsListFromWorkQueue(): void
    {
        $tasks = [
            'one',
            'two',
            'three',
        ];

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lrange'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lrange')
            ->with('working', 0, -1)
            ->willReturn($tasks);

        $queue = new RedisTaskQueue($redis);

        $this->assertSame($tasks, $queue->retrieveInProgressTasks());
    }

    public function testHasWorkingTasksReturnsFalseWhenNoTasksInWorkQueue(): void
    {
        $tasks = [];

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lrange'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lrange')
            ->with('working', 0, -1)
            ->willReturn($tasks);

        $queue = new RedisTaskQueue($redis);

        $this->assertFalse($queue->hasWorkingTasks());
    }

    public function testHasWorkingTasksReturnsTrueWhenOneOrMoreTasksInWorkQueue(): void
    {
        $tasks = [
            'one',
            'two',
            'three',
        ];

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lrange'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lrange')
            ->with('working', 0, -1)
            ->willReturn($tasks);

        $queue = new RedisTaskQueue($redis);

        $this->assertTrue($queue->hasWorkingTasks());
    }
}
