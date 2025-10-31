<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Tests\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Enum\Types;

/**
 * @internal
 */
#[CoversClass(UpdateTimeColumn::class)]
final class UpdateTimeColumnTest extends TestCase
{
    public function testDefaultType(): void
    {
        $updateTimeColumn = new UpdateTimeColumn();
        $this->assertSame(Types::datetime, $updateTimeColumn->type);
    }

    public function testCustomType(): void
    {
        $updateTimeColumn = new UpdateTimeColumn(type: Types::timestamp);
        $this->assertSame(Types::timestamp, $updateTimeColumn->type);
    }

    public function testTargetProperty(): void
    {
        $reflection = new \ReflectionClass(UpdateTimeColumn::class);
        $attributes = $reflection->getAttributes();

        $this->assertCount(1, $attributes);
        $this->assertSame(\Attribute::class, $attributes[0]->getName());

        $attributeInstance = $attributes[0]->newInstance();
        $this->assertInstanceOf(\Attribute::class, $attributeInstance);
        $this->assertSame(\Attribute::TARGET_PROPERTY, $attributeInstance->flags);
    }
}
