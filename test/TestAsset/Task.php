<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue\TestAsset;

use Phly\RedisTaskQueue\TaskInterface;

class Task implements TaskInterface
{
    public function __construct(
        public readonly string $message,
    ) {
    }

    public static function createFromStdClass(object $object): self
    {
        return new self($object->message ?? 'no message');
    }

    public function jsonSerialize(): array
    {
        return [
            '__type'  => $this::class,
            'message' => $this->message,
        ];
    }
}
