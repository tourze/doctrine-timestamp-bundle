<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Traits;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

/**
 * TimestampableAware trait 测试类
 */
class TimestampableAwareTest extends TestCase
{
    /**
     * 创建一个使用 TimestampableAware trait 的测试类
     */
    private function createTestEntity(): object
    {
        return new class {
            use TimestampableAware;
        };
    }

    /**
     * 测试设置和获取 createTime - 正常情况
     */
    public function test_setAndGetCreateTime_withDateTime(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new DateTimeImmutable('2024-01-15 10:30:45');

        $entity->setCreateTime($dateTime);

        $this->assertSame($dateTime, $entity->getCreateTime());
        $this->assertEquals('2024-01-15 10:30:45', $entity->getCreateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * 测试设置和获取 createTime - DateTimeImmutable
     */
    public function test_setAndGetCreateTime_withDateTimeImmutable(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new DateTimeImmutable('2024-01-15 10:30:45');

        $entity->setCreateTime($dateTime);

        $this->assertSame($dateTime, $entity->getCreateTime());
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreateTime());
    }

    /**
     * 测试设置和获取 createTime - null 值
     */
    public function test_setAndGetCreateTime_withNull(): void
    {
        $entity = $this->createTestEntity();

        $entity->setCreateTime(null);

        $this->assertNull($entity->getCreateTime());
    }

    /**
     * 测试设置和获取 updateTime - 正常情况
     */
    public function test_setAndGetUpdateTime_withDateTime(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new DateTimeImmutable('2024-01-15 15:45:30');

        $entity->setUpdateTime($dateTime);

        $this->assertSame($dateTime, $entity->getUpdateTime());
        $this->assertEquals('2024-01-15 15:45:30', $entity->getUpdateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * 测试设置和获取 updateTime - DateTimeImmutable
     */
    public function test_setAndGetUpdateTime_withDateTimeImmutable(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new DateTimeImmutable('2024-01-15 15:45:30');

        $entity->setUpdateTime($dateTime);

        $this->assertSame($dateTime, $entity->getUpdateTime());
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getUpdateTime());
    }

    /**
     * 测试设置和获取 updateTime - null 值
     */
    public function test_setAndGetUpdateTime_withNull(): void
    {
        $entity = $this->createTestEntity();

        $entity->setUpdateTime(null);

        $this->assertNull($entity->getUpdateTime());
    }

    /**
     * 测试 retrieveTimestampArray - 两个时间都有值
     */
    public function test_retrieveTimestampArray_withBothTimes(): void
    {
        $entity = $this->createTestEntity();
        $createTime = new DateTimeImmutable('2024-01-15 10:30:45');
        $updateTime = new DateTimeImmutable('2024-01-15 15:45:30');

        $entity->setCreateTime($createTime);
        $entity->setUpdateTime($updateTime);

        $result = $entity->retrieveTimestampArray();
        $this->assertArrayHasKey('createTime', $result);
        $this->assertArrayHasKey('updateTime', $result);
        $this->assertEquals('2024-01-15 10:30:45', $result['createTime']);
        $this->assertEquals('2024-01-15 15:45:30', $result['updateTime']);
    }

    /**
     * 测试 retrieveTimestampArray - createTime 为 null
     */
    public function test_retrieveTimestampArray_withNullCreateTime(): void
    {
        $entity = $this->createTestEntity();
        $updateTime = new DateTimeImmutable('2024-01-15 15:45:30');

        $entity->setCreateTime(null);
        $entity->setUpdateTime($updateTime);

        $result = $entity->retrieveTimestampArray();
        $this->assertNull($result['createTime']);
        $this->assertEquals('2024-01-15 15:45:30', $result['updateTime']);
    }

    /**
     * 测试 retrieveTimestampArray - updateTime 为 null
     */
    public function test_retrieveTimestampArray_withNullUpdateTime(): void
    {
        $entity = $this->createTestEntity();
        $createTime = new DateTimeImmutable('2024-01-15 10:30:45');

        $entity->setCreateTime($createTime);
        $entity->setUpdateTime(null);

        $result = $entity->retrieveTimestampArray();
        $this->assertEquals('2024-01-15 10:30:45', $result['createTime']);
        $this->assertNull($result['updateTime']);
    }

    /**
     * 测试 retrieveTimestampArray - 两个时间都为 null
     */
    public function test_retrieveTimestampArray_withBothNull(): void
    {
        $entity = $this->createTestEntity();

        $entity->setCreateTime(null);
        $entity->setUpdateTime(null);

        $result = $entity->retrieveTimestampArray();
        $this->assertNull($result['createTime']);
        $this->assertNull($result['updateTime']);
    }

    /**
     * 测试时间格式化 - 验证 Y-m-d H:i:s 格式
     */
    public function test_timestampFormat_verification(): void
    {
        $entity = $this->createTestEntity();
        $createTime = new DateTimeImmutable('2024-12-31 23:59:59');
        $updateTime = new DateTimeImmutable('2024-01-01 00:00:01');

        $entity->setCreateTime($createTime);
        $entity->setUpdateTime($updateTime);

        $result = $entity->retrieveTimestampArray();

        $this->assertEquals('2024-12-31 23:59:59', $result['createTime']);
        $this->assertEquals('2024-01-01 00:00:01', $result['updateTime']);
        
        // 验证格式是否符合预期的正则表达式
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result['createTime']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result['updateTime']);
    }

    /**
     * 测试初始状态 - 验证默认值为 null
     */
    public function test_initialState_shouldBeNull(): void
    {
        $entity = $this->createTestEntity();

        $this->assertNull($entity->getCreateTime());
        $this->assertNull($entity->getUpdateTime());
    }

    /**
     * 测试边界时间值
     */
    public function test_boundaryTimeValues(): void
    {
        $entity = $this->createTestEntity();
        
        // 测试最小时间戳
        $minTime = new DateTimeImmutable('@0'); // Unix epoch
        $entity->setCreateTime($minTime);
        $this->assertEquals('1970-01-01 00:00:00', $entity->getCreateTime()->format('Y-m-d H:i:s'));

        // 测试最大合理时间戳 (2038年问题边界附近)
        $maxTime = new DateTimeImmutable('2037-12-31 23:59:59');
        $entity->setUpdateTime($maxTime);
        $this->assertEquals('2037-12-31 23:59:59', $entity->getUpdateTime()->format('Y-m-d H:i:s'));
    }
}
