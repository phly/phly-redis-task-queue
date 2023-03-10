<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue\Mapper;

use Phly\RedisTaskQueue\Mapper\Exception\ExtractionFailure;
use Phly\RedisTaskQueue\Mapper\Mapper;
use PhlyTest\RedisTaskQueue\TestAsset\Task;
use PhlyTest\RedisTaskQueue\TestAsset\TaskMapper;
use PHPUnit\Framework\TestCase;

use function Phly\RedisTaskQueue\jsonDecode;

class MapperTest extends TestCase
{
    public function testMapperWorkflowWorksAsExpected(): void
    {
        $mapper         = new Mapper();
        $testTaskMapper = new TaskMapper();

        $mapper->attach($testTaskMapper);

        $task = new Task('Task message');
        $json = $mapper->toString($task);
        $this->assertSerializedTask($task, $json);

        $hydrated = $mapper->toObject($json);

        $this->assertInstanceOf(Task::class, $hydrated);
        $this->assertSame($task->message, $task->message);

        $mapper->detach($testTaskMapper);

        $this->expectException(ExtractionFailure::class);
        $mapper->toString($task);
    }

    private function assertSerializedTask(Task $task, string $json): void
    {
        $serialized = jsonDecode($json);
        $this->assertArrayHasKey('__type', $serialized);
        $this->assertSame(Task::class, $serialized['__type']);
        $this->assertArrayHasKey('message', $serialized);
        $this->assertSame($task->message, $serialized['message']);
    }
}
