<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue\TestAsset;

use Phly\RedisTaskQueue\Mapper\MapperInterface;

class TaskMapper implements MapperInterface
{
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
}
