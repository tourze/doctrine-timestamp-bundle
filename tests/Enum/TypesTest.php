<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\DoctrineTimestampBundle\Enum\Types;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(Types::class)]
final class TypesTest extends AbstractEnumTestCase
{
    public function testCompareTypes(): void
    {
        $type1 = Types::datetime;
        $type2 = Types::datetime;
        $type3 = Types::timestamp;

        $this->assertEquals($type1, $type2);
        $this->assertNotEquals($type1, $type3);
        $this->assertSame($type1, $type2);
    }

    public function testCases(): void
    {
        $cases = Types::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(Types::datetime, $cases);
        $this->assertContains(Types::timestamp, $cases);
    }

    /**
     * @param array<string, string> $expected
     */
    #[TestWith([Types::datetime, ['value' => 'datetime', 'label' => '日期时间']])]
    #[TestWith([Types::timestamp, ['value' => 'timestamp', 'label' => '时间戳']])]
    public function testToArray(Types $case, array $expected): void
    {
        $result = $case->toArray();
        $this->assertEquals($expected, $result);
    }
}
