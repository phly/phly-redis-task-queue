<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue;

use Phly\RedisTaskQueue\Mapper\Mapper;
use Phly\RedisTaskQueue\Mapper\MapperInterface;
use Phly\RedisTaskQueue\Worker;
use PhlyTest\RedisTaskQueue\TestAsset\TaskMapper;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stringable;

class WorkerTest extends TestCase
{
    public function testProcessingDecodesJsonToTaskAndDispatchesAsEvent(): void
    {
        $spy = new class {
            public bool $toggle = false;
        };

        $listener = function (TestAsset\Task $task) use ($spy): void {
            $this->assertNotEmpty($task->message);
            $spy->toggle = true;
        };

        $dispatcher = new class($listener) implements EventDispatcherInterface {
            /** @var callable */
            private $listener;

            public function __construct(callable $listener)
            {
                $this->listener = $listener;
            }

            public function dispatch(object $event): void
            {
                ($this->listener)($event);
            }
        };

        $mapper = new Mapper();
        $mapper->attach(new TaskMapper());

        $worker = new Worker($mapper, $dispatcher);
        $worker->process('{"__type":"PhlyTest\\\\RedisTaskQueue\\\\TestAsset\\\\Task","message":"Task message"}');

        $this->assertTrue($spy->toggle);
    }

    public function testProcessingLogsTaskJson(): void
    {
        $taskJson = '{"__type":"PhlyTest\\\\RedisTaskQueue\\\\TestAsset\\\\Task","message":"Task message"}';

        $listener = function (): void {
        };

        $dispatcher = new class($listener) implements EventDispatcherInterface {
            /** @var callable */
            private $listener;

            public function __construct(callable $listener)
            {
                $this->listener = $listener;
            }

            public function dispatch(object $event): void
            {
                ($this->listener)($event);
            }
        };

        $logger = new class implements LoggerInterface {
            public array $context = [];

            public function emergency(string|Stringable $message, array $context = []): void
            {
                throw new RuntimeException('Should not be raised');
            }

            public function alert(string|Stringable $message, array $context = []): void
            {
                throw new RuntimeException('Should not be raised');
            }

            public function critical(string|Stringable $message, array $context = []): void
            {
                throw new RuntimeException('Should not be raised');
            }

            public function error(string|Stringable $message, array $context = []): void
            {
                throw new RuntimeException('Should not be raised');
            }

            public function warning(string|Stringable $message, array $context = []): void
            {
                throw new RuntimeException('Should not be raised');
            }

            public function notice(string|Stringable $message, array $context = []): void
            {
                throw new RuntimeException('Should not be raised');
            }

            public function info(string|Stringable $message, array $context = []): void
            {
                $this->context = $context;
            }

            public function debug(string|Stringable $message, array $context = []): void
            {
                throw new RuntimeException('Should not be raised');
            }

            public function log($level, string|Stringable $message, array $context = []): void
            {
                throw new RuntimeException('Should not be raised');
            }
        };

        $mapper = new Mapper();
        $mapper->attach(new TaskMapper());

        $worker = new Worker($mapper, $dispatcher, $logger);
        $worker->process($taskJson);

        $this->assertSame(['task' => $taskJson], $logger->context);
    }
}
