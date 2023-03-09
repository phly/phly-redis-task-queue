<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue\Mapper;

use Phly\RedisTaskQueue\Mapper\Exception\ExtractionFailure;
use Phly\RedisTaskQueue\Mapper\Mapper;
use Phly\RedisTaskQueue\Mapper\MapperInterface;
use PhlyTest\RedisTaskQueue\TestAsset\Task;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    public function testMapperWorkflowWorksAsExpected(): void
    {
        $mapper = new Mapper();
        $testTaskMapper = new class() implements MapperInterface {
            public function handlesArray(array $serialized): bool
            {
                return $serialized['__type'] === Task::class;
            }

            public function handlesObject(object $object): bool
            {
                return $object instanceof Task;
            }

            /** @param Task $object */
            public function extract(object $object): array
            {
                return [
                    '__type'  => Task::class,
                    'message' => $object->message,
                ];
            }

            public function hydrate(array $serialized): Task
            {
                $message = $serialized['message'] ?? '';
                assert(is_string($message));

                return new Task($message);
            }
        };

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
