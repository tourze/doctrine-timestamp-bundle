<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineTimestampBundle\Enum\Types;

class TypesTest extends TestCase
{
    public function testDatetimeType(): void
    {
        $type = Types::datetime;
        $this->assertEquals('datetime', $type->value);
    }
    
    public function testTimestampType(): void
    {
        $type = Types::timestamp;
        $this->assertEquals('timestamp', $type->value);
    }
    
    public function testCompareTypes(): void
    {
        $type1 = Types::datetime;
        $type2 = Types::datetime;
        $type3 = Types::timestamp;
        
        $this->assertEquals($type1, $type2);
        $this->assertNotEquals($type1, $type3);
        $this->assertSame($type1, $type2);
        $this->assertNotSame($type1, $type3);
    }
    
    public function testFromValue(): void
    {
        $type1 = Types::from('datetime');
        $type2 = Types::from('timestamp');
        
        $this->assertEquals(Types::datetime, $type1);
        $this->assertEquals(Types::timestamp, $type2);
    }
    
    public function testTryFromValue(): void
    {
        $type1 = Types::tryFrom('datetime');
        $type2 = Types::tryFrom('timestamp');
        $type3 = Types::tryFrom('nonexistent');
        
        $this->assertEquals(Types::datetime, $type1);
        $this->assertEquals(Types::timestamp, $type2);
        $this->assertNull($type3);
    }
    
    public function testCases(): void
    {
        $cases = Types::cases();
        
        $this->assertCount(2, $cases);
        $this->assertContains(Types::datetime, $cases);
        $this->assertContains(Types::timestamp, $cases);
    }
}
