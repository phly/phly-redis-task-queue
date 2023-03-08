<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue\EventDispatcher;

use Phly\RedisTaskQueue\EventDispatcher\DeferredEvent;
use Phly\RedisTaskQueue\EventDispatcher\DeferredEventListener;
use Phly\RedisTaskQueue\RedisTaskQueue;
use PhlyTest\RedisTaskQueue\TestAsset\Task;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class DeferredEventListenerTest extends TestCase
{
    public function testQueuesWrappedTask(): void
    {
        $task = new Task('Task message');

        /** @var MockObject&Client $redis */
        $redis = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->addMethods(['lpush'])
            ->getMock();

        $redis
            ->expects($this->once())
            ->method('lpush')
            ->with('pending', [json_encode($task, flags: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);

        $queue    = new RedisTaskQueue($redis);
        $listener = new DeferredEventListener($queue);
        $event    = new DeferredEvent($task);

        $this->assertNull($listener($event));
    }
}
