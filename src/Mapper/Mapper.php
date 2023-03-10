<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue\Mapper;

use JsonException;
use Phly\RedisTaskQueue\Exception\TaskMissingType;
use Phly\RedisTaskQueue\Mapper\Exception\ExtractionFailure;
use Phly\RedisTaskQueue\Mapper\Exception\InvalidSerializationFailure;
use Phly\RedisTaskQueue\Mapper\Exception\UnknownMapperFailure;

use function array_key_exists;
use function array_search;
use function array_values;
use function in_array;
use function is_string;
use function Phly\RedisTaskQueue\jsonDecode;
use function Phly\RedisTaskQueue\jsonEncode;

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
     * @throws ExtractionFailure
     * @throws JsonException
     */
    public function toString(object $object): string
    {
        foreach ($this->mappers as $mapper) {
            if (! $mapper->handlesObject($object)) {
                continue;
            }

            $serialized = $mapper->castToArray($object);
            if (! array_key_exists('__type', $serialized)) {
                throw ExtractionFailure::forMissingTypeInSerialization($object, $serialized);
            }

            return jsonEncode($serialized);
        }

        throw ExtractionFailure::forObject($object);
    }

    /**
     * @throws InvalidSerializationFailure
     * @throws TaskMissingType
     * @throws UnknownMapperFailure
     * @throws JsonException
     */
    public function toObject(string $json): object
    {
        $serialized = jsonDecode($json);

        if (! array_key_exists('__type', $serialized)) {
            throw InvalidSerializationFailure::forMissingType($serialized);
        }

        if (! is_string($serialized['__type'])) {
            throw TaskMissingType::forJson($json);
        }

        foreach ($this->mappers as $mapper) {
            if (! $mapper->handlesArray($serialized)) {
                continue;
            }

            return $mapper->castToObject($serialized);
        }

        throw UnknownMapperFailure::forHydration($json);
    }

    public function canCastToObject(string $json): bool
    {
        try {
            $serialized = jsonDecode($json);
        } catch (JsonException) {
            return false;
        }

        if (! array_key_exists('__type', $serialized)) {
            return false;
        }

        foreach ($this->mappers as $mapper) {
            if (! $mapper->handlesArray($serialized)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
