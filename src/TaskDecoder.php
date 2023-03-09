<?php

declare(strict_types=1);

namespace Phly\RedisTaskQueue;

use Closure;
use stdClass;
use Webmozart\Assert\Assert;

use function class_exists;
use function class_implements;
use function in_array;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/** @internal */
final class TaskDecoder
{
    /**
     * @throws Exception\TaskMissingType
     * @throws Exception\TaskUnknownType
     */
    public function decode(string $json): TaskInterface
    {
        $object = $this->validate($json);
        $class  = $object->__type;

        $constructor = Closure::fromCallable([$class, 'createFromStdClass']);
        return $constructor($object);
    }

    /**
     * @psalm-return object{__type:class-string<TaskInterface>}
     * @throws Exception\TaskMissingType
     * @throws Exception\TaskUnknownType
     */
    public function validate(string $json): object
    {
        $object = json_decode($json, associative: false, flags: JSON_THROW_ON_ERROR);
        Assert::isInstanceOf($object, stdClass::class);

        if (! isset($object->__type)) {
            throw Exception\TaskMissingType::forJson($json);
        }

        if (! is_string($object->__type)) {
            throw Exception\TaskUnknownType::forType($object->__type);
        }

        if (! class_exists($object->__type)) {
            throw Exception\TaskUnknownType::forClass($object->__type);
        }

        $class      = $object->__type;
        $implements = class_implements($class);
        if (false === $implements || ! in_array(TaskInterface::class, $implements, true)) {
            throw Exception\TaskUnknownType::forNonTaskType($class);
        }

        return $object;
    }
}
