<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue\EventDispatcher;

use Phly\RedisTaskQueue\EventDispatcher\DeferredEvent;
use Phly\RedisTaskQueue\EventDispatcher\DeferredEventListener;
use Phly\RedisTaskQueue\Mapper\Mapper;
use Phly\RedisTaskQueue\RedisTaskQueue;
use PhlyTest\RedisTaskQueue\TestAsset\Task;
use PhlyTest\RedisTaskQueue\TestAsset\TaskMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class DeferredEventListenerTest extends TestCase
{
    public function testQueuesWrappedTask(): void
    {
        $mapper = new Mapper();
        $mapper->attach(new TaskMapper());
        $task     = new Task('Task message');
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

        $queue    = new RedisTaskQueue($redis, $mapper);
        $listener = new DeferredEventListener($queue);
        $event    = new DeferredEvent($task);

        $this->assertNull($listener($event));
    }
}
