<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper;

interface MapperInterface
{
    /**
     * Can this implementation hydrate the given array type?
     *
     * @param array{__type: string, ...} $serialized
     */
    public function handlesArray(array $serialized): bool;

    /**
     * Can this implementation extract the given object type?
     */
    public function handlesObject(object $object): bool;

    /** @return array{__type: string, ...} */
    public function extract(object $object): array;

    /** @param array{__type: string, ...} $serialized */
    public function hydrate(array $serialized): object;
}
