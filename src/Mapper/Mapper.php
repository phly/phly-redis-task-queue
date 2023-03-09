<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper;

use function array_key_exists;
use function array_search;
use function array_values;
use function in_array;

final class Mapper
{
    /** @psalm-var list<MapperInterface> */
    private array $mappers = [];

    public function attach(MapperInterface $mapper): void
    {
        if (in_array($mapper, $this->mappers, true)) {
            return;
        }

        $this->mappers[] = $mapper;
    }

    public function detach(MapperInterface $mapper): void
    {
        $index = array_search($mapper, $this->mappers, strict: true);

        if (false === $index) {
            return;
        }

        unset($this->mappers[$index]);
        $this->mappers = array_values($this->mappers);
    }

    /**
     * @return array{__type: string, ...}
     * @throws Exception\ExtractionFailure
     */
    public function extract(object $object): array
    {
        foreach ($this->mappers as $mapper) {
            if (! $mapper->handlesObject($object)) {
                continue;
            }

            $serialized = $mapper->extract($object);
            if (! array_key_exists('__type', $serialized)) {
                throw Exception\ExtractionFailure::forMissingTypeInSerialization($object, $serialized);
            }

            return $serialized;
        }

        throw Exception\ExtractionFailure::forObject($object);
    }

    /**
     * @psalm-param array{__type: string, ...} $serialized
     * @throws Exception\InvalidSerializationFailure
     * @throws Exception\UnknownMapperFailure
     */
    public function hydrate(array $serialized): object
    {
        if (! array_key_exists('__type', $serialized)) {
            throw Exception\InvalidSerializationFailure::forMissingType($serialized);
        }

        foreach ($this->mappers as $mapper) {
            if (! $mapper->handlesArray($serialized)) {
                continue;
            }

            return $mapper->hydrate($serialized);
        }

        throw Exception\UnknownMapperFailure::forHydration($serialized);
    }
}
