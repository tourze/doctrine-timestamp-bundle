<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Enum\Types;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

class AttributeTest extends TestCase
{
    use TimestampableAware;
    public function testCreateTimeColumnDefaults(): void
    {
        $attribute = new CreateTimeColumn();
        $this->assertEquals(Types::datetime, $attribute->type);
    }
    
    public function testCreateTimeColumnCustomType(): void
    {
        $attribute = new CreateTimeColumn(Types::timestamp);
        $this->assertEquals(Types::timestamp, $attribute->type);
    }
    
    public function testUpdateTimeColumnDefaults(): void
    {
        $attribute = new UpdateTimeColumn();
        $this->assertEquals(Types::datetime, $attribute->type);
    }
    
    public function testUpdateTimeColumnCustomType(): void
    {
        $attribute = new UpdateTimeColumn(Types::timestamp);
        $this->assertEquals(Types::timestamp, $attribute->type);
    }
    
    public function testCreateTimeColumnTargetProperty(): void
    {
        $reflectionClass = new \ReflectionClass(CreateTimeColumn::class);
        $attributes = $reflectionClass->getAttributes(\Attribute::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertEquals(\Attribute::TARGET_PROPERTY, $attribute->flags);
    }
    
    public function testUpdateTimeColumnTargetProperty(): void
    {
        $reflectionClass = new \ReflectionClass(UpdateTimeColumn::class);
        $attributes = $reflectionClass->getAttributes(\Attribute::class);
        
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertEquals(\Attribute::TARGET_PROPERTY, $attribute->flags);
    }
} 