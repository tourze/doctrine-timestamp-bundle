<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Enum\Types;

class CreateTimeColumnTest extends TestCase
{
    public function testDefaultType()
    {
        $createTimeColumn = new CreateTimeColumn();
        $this->assertSame(Types::datetime, $createTimeColumn->type);
    }

    public function testCustomType()
    {
        $createTimeColumn = new CreateTimeColumn(type: Types::timestamp);
        $this->assertSame(Types::timestamp, $createTimeColumn->type);
    }

    public function testTargetProperty()
    {
        $reflection = new \ReflectionClass(CreateTimeColumn::class);
        $attributes = $reflection->getAttributes();

        $this->assertCount(1, $attributes);
        $this->assertSame(\Attribute::class, $attributes[0]->getName());

        $attributeInstance = $attributes[0]->newInstance();
        $this->assertSame(\Attribute::TARGET_PROPERTY, $attributeInstance->flags);
    }
}
