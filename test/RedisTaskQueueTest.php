<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue;

use Phly\RedisTaskQueue\Mapper\Mapper;
use Phly\RedisTaskQueue\RedisTaskQueue;
use PhlyTest\RedisTaskQueue\TestAsset\Task;
use PhlyTest\RedisTaskQueue\TestAsset\TaskMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;

use function array_map;

class RedisTaskQueueTest extends TestCase
{
    public function testQueueingPushesToRedis(): void
    {
        $task   = new Task('Task message');
        $mapper = new Mapper();
        $mapper->attach(new TaskMapper());
        $taskJson = $mapper->toString($task);

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lpush'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lpush')
            ->with('pending', [$taskJson]);

        $queue = new RedisTaskQueue($redis, $mapper);

        $this->assertNull($queue->queue($task));
    }

    public function testRetrievePendingTasksPullsListFromWaitQueue(): void
    {
        $mapper = new Mapper();
        $mapper->attach(new TaskMapper());

        $tasks     = [
            new Task('one'),
            new Task('two'),
            new Task('three'),
        ];
        $tasksJson = array_map(function (Task $task) use ($mapper): string {
            return $mapper->toString($task);
        }, $tasks);

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lrange'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lrange')
            ->with('pending', 0, -1)
            ->willReturn($tasksJson);

        $queue = new RedisTaskQueue($redis, $mapper);

        $this->assertEquals($tasks, $queue->retrievePendingTasks());
    }

    public function testHasPendingTasksReturnsFalseWhenNoTasksInWaitQueue(): void
    {
        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lrange'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lrange')
            ->with('pending', 0, -1)
            ->willReturn([]);

        $queue = new RedisTaskQueue($redis, new Mapper());

        $this->assertFalse($queue->hasPendingTasks());
    }

    public function testHasPendingTasksReturnsTrueWhenOneOrMoreTasksInWaitQueue(): void
    {
        $mapper = new Mapper();
        $mapper->attach(new TaskMapper());

        $tasks     = [
            new Task('one'),
            new Task('two'),
            new Task('three'),
        ];
        $tasksJson = array_map(function (Task $task) use ($mapper): string {
            return $mapper->toString($task);
        }, $tasks);

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lrange'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lrange')
            ->with('pending', 0, -1)
            ->willReturn($tasksJson);

        $queue = new RedisTaskQueue($redis, $mapper);

        $this->assertTrue($queue->hasPendingTasks());
    }

    public function testRetrieveNextTaskPopsTaskFromWaitQueueAndPushesToWorkQueue(): void
    {
        $mapper = new Mapper();
        $mapper->attach(new TaskMapper());

        $task      = new Task('one');
        $tasks     = [
            new Task('two'),
            new Task('three'),
            $task,
        ];
        $tasksJson = array_map(function (Task $task) use ($mapper): string {
            return $mapper->toString($task);
        }, $tasks);

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['rpoplpush'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('rpoplpush')
            ->with('pending', 'working')
            ->willReturn($mapper->toString($task));

        $queue = new RedisTaskQueue($redis, $mapper);

        $this->assertEquals($task, $queue->retrieveNextTask());
    }

    public function testRetrieveInProgressTasksPullsListFromWorkQueue(): void
    {
        $mapper = new Mapper();
        $mapper->attach(new TaskMapper());

        $tasks     = [
            new Task('one'),
            new Task('two'),
            new Task('three'),
        ];
        $tasksJson = array_map(function (Task $task) use ($mapper): string {
            return $mapper->toString($task);
        }, $tasks);

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lrange'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lrange')
            ->with('working', 0, -1)
            ->willReturn($tasksJson);

        $queue = new RedisTaskQueue($redis, $mapper);

        $this->assertEquals($tasks, $queue->retrieveInProgressTasks());
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

        $queue = new RedisTaskQueue($redis, new Mapper());

        $this->assertFalse($queue->hasWorkingTasks());
    }

    public function testHasWorkingTasksReturnsTrueWhenOneOrMoreTasksInWorkQueue(): void
    {
        $mapper = new Mapper();
        $mapper->attach(new TaskMapper());

        $tasks     = [
            new Task('one'),
            new Task('two'),
            new Task('three'),
        ];
        $tasksJson = array_map(function (Task $task) use ($mapper): string {
            return $mapper->toString($task);
        }, $tasks);

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lrange'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lrange')
            ->with('working', 0, -1)
            ->willReturn($tasksJson);

        $queue = new RedisTaskQueue($redis, $mapper);

        $this->assertTrue($queue->hasWorkingTasks());
    }
}
