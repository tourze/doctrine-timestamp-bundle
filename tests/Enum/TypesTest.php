<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineTimestampBundle\Enum\Types;

class TypesTest extends TestCase
{
    public function testEnumValues()
    {
        $this->assertSame('datetime', Types::datetime->value);
        $this->assertSame('timestamp', Types::timestamp->value);
    }

    public function testEnumComparison()
    {
        $type1 = Types::datetime;
        $type2 = Types::datetime;
        $type3 = Types::timestamp;

        $this->assertTrue($type1 === $type2);
        $this->assertFalse($type1 === $type3);
        $this->assertTrue($type1 === Types::datetime);
        $this->assertFalse($type1 === Types::timestamp);
    }

    public function testEnumCases()
    {
        $cases = Types::cases();
        $this->assertCount(2, $cases);
        $this->assertContains(Types::datetime, $cases);
        $this->assertContains(Types::timestamp, $cases);
    }
}
