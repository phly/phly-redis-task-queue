<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue;

use InvalidArgumentException;
use JsonException;
use Phly\RedisTaskQueue\Exception\TaskMissingType;
use Phly\RedisTaskQueue\Exception\TaskUnknownType;
use Phly\RedisTaskQueue\TaskDecoder;
use PHPUnit\Framework\TestCase;

class TaskDecoderTest extends TestCase
{
    private TaskDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new TaskDecoder();
    }

    public function testDecoderRaisesJsonExceptionOnParseError(): void
    {
        $json = 'this: ["is": "not", a]; valid "JSON" _string_';
        $this->expectException(JsonException::class);
        $this->decoder->decode($json);
    }

    /** @psalm-return iterable<string, list<string>> */
    public static function provideInvalidJSONValues(): iterable
    {
        yield 'null'   => ['null'];
        yield 'int'    => ['1'];
        yield 'float'  => ['1.1'];
        yield 'string' => ['"string"'];
    }

    /** @dataProvider provideInvalidJSONValues */
    public function testDecodingRaisesTypeErrorWhenNotDecodingToObject(string $json): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->decoder->decode($json);
    }

    public function testDecodingRaisesTaskMissingTypeForObjectMissingTypeProperty(): void
    {
        $json = '{"key": "value"}';
        $this->expectException(TaskMissingType::class);
        $this->decoder->decode($json);
    }

    public function testDecodingRaisesTaskUnknownTypeIfNonStringTypePropertyReturned(): void
    {
        $json = '{"__type": 1, "key": "value"}';
        $this->expectException(TaskUnknownType::class);
        $this->decoder->decode($json);
    }

    public function testDecodingRaisesTaskUnknownTypeIfTypeDoesNotResolveToClass(): void
    {
        $json = '{"__type": "not-a-class", "key": "value"}';
        $this->expectException(TaskUnknownType::class);
        $this->decoder->decode($json);
    }

    public function testDecodingRaisesTaskUnknownTypeIfResolvedClassDoesNotImplementTask(): void
    {
        $json = '{"__type": "stdClass", "key": "value"}';
        $this->expectException(TaskUnknownType::class);
        $this->decoder->decode($json);
    }

    public function testDecodingReturnsTaskInstanceImplementingSpecifiedType(): void
    {
        $json = '{"__type":"PhlyTest\\\\RedisTaskQueue\\\\TestAsset\\\\Task","message":"Task message"}';
        $task = $this->decoder->decode($json);

        $this->assertInstanceOf(TestAsset\Task::class, $task);
        $this->assertSame('Task message', $task->message);
    }
}
