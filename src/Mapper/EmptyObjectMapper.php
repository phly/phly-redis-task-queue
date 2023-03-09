<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper;

final class EmptyObjectMapper implements MapperInterface
{
    public function __construct(
        /** @psalm-var class-string $class */
        private readonly string $class,
    ) {
    }

    public function handlesArray(array $serialized): bool
    {
        return $serialized['__type'] === $this->class;
    }

    public function handlesObject(object $object): bool
    {
        return $object instanceof $this->class;
    }

    public function extract(object $object): array
    {
        return ['__type' => $this->class];
    }

    public function hydrate(array $serialized): object
    {
        return new ($this->class)();
    }
}
