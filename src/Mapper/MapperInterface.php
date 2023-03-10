<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper;

interface MapperInterface
{
    /**
     * Can this implementation cast the given array to an object?
     *
     * @psalm-param array{__type: string, ...} $serialized
     */
    public function handlesArray(array $serialized): bool;

    /**
     * Can this implementation extract the given object type?
     */
    public function handlesObject(object $object): bool;

    /** @return array{__type: string, ...} */
    public function castToArray(object $object): array;

    /** @psalm-param array{__type: string, ...} $serialized */
    public function castToObject(array $serialized): object;
}
