<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Tests\Traits;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(TimestampableAware::class)]
#[RunTestsInSeparateProcesses]
final class TimestampableAwareTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 简单的单元测试不需要特殊的设置
    }

    /**
     * 创建一个使用 TimestampableAware trait 的测试类
     */
    private function createTestEntity(): TestEntityWithTimestamp
    {
        return new TestEntityWithTimestamp();
    }

    /**
     * 测试设置和获取 createTime - 正常情况
     */
    public function testSetAndGetCreateTimeWithDateTime(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new \DateTimeImmutable('2024-01-15 10:30:45');

        $entity->setCreateTime($dateTime);

        $this->assertSame($dateTime, $entity->getCreateTime());
        $createdTime = $entity->getCreateTime();
        $this->assertEquals('2024-01-15 10:30:45', $createdTime->format('Y-m-d H:i:s'));
    }

    /**
     * 测试设置和获取 createTime - DateTimeImmutable
     */
    public function testSetAndGetCreateTimeWithDateTimeImmutable(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new \DateTimeImmutable('2024-01-15 10:30:45');

        $entity->setCreateTime($dateTime);

        $this->assertSame($dateTime, $entity->getCreateTime());
        $createdTime = $entity->getCreateTime();
        $this->assertInstanceOf(\DateTimeInterface::class, $createdTime);
    }

    /**
     * 测试设置和获取 createTime - null 值
     */
    public function testSetAndGetCreateTimeWithNull(): void
    {
        $entity = $this->createTestEntity();

        $entity->setCreateTime(null);

        $this->assertNull($entity->getCreateTime());
    }

    /**
     * 测试设置和获取 updateTime - 正常情况
     */
    public function testSetAndGetUpdateTimeWithDateTime(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new \DateTimeImmutable('2024-01-15 15:45:30');

        $entity->setUpdateTime($dateTime);

        $this->assertSame($dateTime, $entity->getUpdateTime());
        $updateTime = $entity->getUpdateTime();
        $this->assertEquals('2024-01-15 15:45:30', $updateTime->format('Y-m-d H:i:s'));
    }

    /**
     * 测试设置和获取 updateTime - DateTimeImmutable
     */
    public function testSetAndGetUpdateTimeWithDateTimeImmutable(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new \DateTimeImmutable('2024-01-15 15:45:30');

        $entity->setUpdateTime($dateTime);

        $this->assertSame($dateTime, $entity->getUpdateTime());
        $updateTime = $entity->getUpdateTime();
        $this->assertInstanceOf(\DateTimeInterface::class, $updateTime);
    }

    /**
     * 测试设置和获取 updateTime - null 值
     */
    public function testSetAndGetUpdateTimeWithNull(): void
    {
        $entity = $this->createTestEntity();

        $entity->setUpdateTime(null);

        $this->assertNull($entity->getUpdateTime());
    }

    /**
     * 测试 retrieveTimestampArray - 两个时间都有值
     */
    public function testRetrieveTimestampArrayWithBothTimes(): void
    {
        $entity = $this->createTestEntity();
        $createTime = new \DateTimeImmutable('2024-01-15 10:30:45');
        $updateTime = new \DateTimeImmutable('2024-01-15 15:45:30');

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
    public function testRetrieveTimestampArrayWithNullCreateTime(): void
    {
        $entity = $this->createTestEntity();
        $updateTime = new \DateTimeImmutable('2024-01-15 15:45:30');

        $entity->setCreateTime(null);
        $entity->setUpdateTime($updateTime);

        $result = $entity->retrieveTimestampArray();
        $this->assertNull($result['createTime']);
        $this->assertEquals('2024-01-15 15:45:30', $result['updateTime']);
    }

    /**
     * 测试 retrieveTimestampArray - updateTime 为 null
     */
    public function testRetrieveTimestampArrayWithNullUpdateTime(): void
    {
        $entity = $this->createTestEntity();
        $createTime = new \DateTimeImmutable('2024-01-15 10:30:45');

        $entity->setCreateTime($createTime);
        $entity->setUpdateTime(null);

        $result = $entity->retrieveTimestampArray();
        $this->assertEquals('2024-01-15 10:30:45', $result['createTime']);
        $this->assertNull($result['updateTime']);
    }

    /**
     * 测试 retrieveTimestampArray - 两个时间都为 null
     */
    public function testRetrieveTimestampArrayWithBothNull(): void
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
    public function testTimestampFormatVerification(): void
    {
        $entity = $this->createTestEntity();
        $createTime = new \DateTimeImmutable('2024-12-31 23:59:59');
        $updateTime = new \DateTimeImmutable('2024-01-01 00:00:01');

        $entity->setCreateTime($createTime);
        $entity->setUpdateTime($updateTime);

        $result = $entity->retrieveTimestampArray();

        $this->assertEquals('2024-12-31 23:59:59', $result['createTime']);
        $this->assertEquals('2024-01-01 00:00:01', $result['updateTime']);

        // 验证格式是否符合预期的正则表达式
        $this->assertIsString($result['createTime']);
        $this->assertIsString($result['updateTime']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result['createTime']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result['updateTime']);
    }

    /**
     * 测试初始状态 - 验证默认值为 null
     */
    public function testInitialStateShouldBeNull(): void
    {
        $entity = $this->createTestEntity();

        $this->assertNull($entity->getCreateTime());
        $this->assertNull($entity->getUpdateTime());
    }

    /**
     * 测试边界时间值
     */
    public function testBoundaryTimeValues(): void
    {
        $entity = $this->createTestEntity();

        // 测试最小时间戳
        $minTime = new \DateTimeImmutable('@0'); // Unix epoch
        $entity->setCreateTime($minTime);
        $createTime = $entity->getCreateTime();
        $this->assertNotNull($createTime);
        $this->assertEquals('1970-01-01 00:00:00', $createTime->format('Y-m-d H:i:s'));

        // 测试最大合理时间戳 (2038年问题边界附近)
        $maxTime = new \DateTimeImmutable('2037-12-31 23:59:59');
        $entity->setUpdateTime($maxTime);
        $updateTime = $entity->getUpdateTime();
        $this->assertNotNull($updateTime);
        $this->assertEquals('2037-12-31 23:59:59', $updateTime->format('Y-m-d H:i:s'));
    }
}
