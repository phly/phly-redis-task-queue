<?php

declare(strict_types=1);

namespace PhlyTest\RedisTaskQueue\Mapper;

use Phly\RedisTaskQueue\Mapper\EmptyObjectMapper;
use PhlyTest\RedisTaskQueue\TestAsset\EmptyObject;
use PHPUnit\Framework\TestCase;

class EmptyObjectMapperTest extends TestCase
{
    public function testMapperHandlesObjectOfKnownType(): EmptyObject
    {
        $mapper = new EmptyObjectMapper(EmptyObject::class);
        $object = new EmptyObject();

        $this->assertTrue($mapper->handlesObject($object));
        return $object;
    }

    /** @depends testMapperHandlesObjectOfKnownType */
    public function testMapperCanCastToArray(EmptyObject $object): void
    {
        $mapper     = new EmptyObjectMapper(EmptyObject::class);
        $serialized = $mapper->castToArray($object);
        $this->assertEquals(['__type' => EmptyObject::class], $serialized);
    }

    public function testMapperHandlesArrayForKnownType(): array
    {
        $mapper     = new EmptyObjectMapper(EmptyObject::class);
        $serialized = ['__type' => EmptyObject::class];

        $this->assertTrue($mapper->handlesArray($serialized));
        return $serialized;
    }

    /** @depends testMapperHandlesArrayForKnownType */
    public function testMapperCanCastToObject(array $serialized): void
    {
        $mapper = new EmptyObjectMapper(EmptyObject::class);
        $object = $mapper->castToObject($serialized);
        $this->assertInstanceOf(EmptyObject::class, $object);
    }
}
