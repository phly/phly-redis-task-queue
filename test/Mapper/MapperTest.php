<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue\Mapper;

use Phly\RedisTaskQueue\Mapper\Exception\ExtractionFailure;
use Phly\RedisTaskQueue\Mapper\Mapper;
use PhlyTest\RedisTaskQueue\TestAsset\Task;
use PhlyTest\RedisTaskQueue\TestAsset\TaskMapper;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    public function testMapperWorkflowWorksAsExpected(): void
    {
        $mapper         = new Mapper();
        $testTaskMapper = new TaskMapper();

        $mapper->attach($testTaskMapper);

        $task = new Task('Task message');
        $serialized = $mapper->extract($task);

        $this->assertArrayHasKey('__type', $serialized);
        $this->assertSame(Task::class, $serialized['__type']);
        $this->assertArrayHasKey('message', $serialized);
        $this->assertSame($task->message, $serialized['message']);

        $hydrated = $mapper->hydrate($serialized);

        $this->assertInstanceOf(Task::class, $hydrated);
        $this->assertSame($task->message, $task->message);

        $mapper->detach($testTaskMapper);

        $this->expectException(ExtractionFailure::class);
        $mapper->extract($task);
    }
}
