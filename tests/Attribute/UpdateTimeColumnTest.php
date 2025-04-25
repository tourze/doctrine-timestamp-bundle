<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Enum\Types;

class UpdateTimeColumnTest extends TestCase
{
    public function testDefaultType()
    {
        $updateTimeColumn = new UpdateTimeColumn();
        $this->assertSame(Types::datetime, $updateTimeColumn->type);
    }

    public function testCustomType()
    {
        $updateTimeColumn = new UpdateTimeColumn(type: Types::timestamp);
        $this->assertSame(Types::timestamp, $updateTimeColumn->type);
    }

    public function testTargetProperty()
    {
        $reflection = new \ReflectionClass(UpdateTimeColumn::class);
        $attributes = $reflection->getAttributes();

        $this->assertCount(1, $attributes);
        $this->assertSame(\Attribute::class, $attributes[0]->getName());

        $attributeInstance = $attributes[0]->newInstance();
        $this->assertSame(\Attribute::TARGET_PROPERTY, $attributeInstance->flags);
    }
}
