<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Tests\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Enum\Types;

/**
 * @internal
 */
#[CoversClass(CreateTimeColumn::class)]
final class CreateTimeColumnTest extends TestCase
{
    public function testDefaultType(): void
    {
        $createTimeColumn = new CreateTimeColumn();
        $this->assertSame(Types::datetime, $createTimeColumn->type);
    }

    public function testCustomType(): void
    {
        $createTimeColumn = new CreateTimeColumn(type: Types::timestamp);
        $this->assertSame(Types::timestamp, $createTimeColumn->type);
    }

    public function testTargetProperty(): void
    {
        $reflection = new \ReflectionClass(CreateTimeColumn::class);
        $attributes = $reflection->getAttributes();

        $this->assertCount(1, $attributes);
        $this->assertSame(\Attribute::class, $attributes[0]->getName());

        $attributeInstance = $attributes[0]->newInstance();
        $this->assertInstanceOf(\Attribute::class, $attributeInstance);
        $this->assertSame(\Attribute::TARGET_PROPERTY, $attributeInstance->flags);
    }
}
