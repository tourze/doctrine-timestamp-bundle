<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Traits;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;

/**
 * CreateTimeAware trait 测试类
 */
class CreateTimeAwareTest extends TestCase
{
    /**
     * 测试设置和获取 createTime - 正常情况
     */
    public function test_setAndGetCreateTime_withDateTime(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new DateTimeImmutable('2024-01-15 10:30:45');

        $result = $entity->setCreateTime($dateTime);

        $this->assertSame($entity, $result, 'setCreateTime should return self for method chaining');
        $this->assertSame($dateTime, $entity->getCreateTime());
        $this->assertEquals('2024-01-15 10:30:45', $entity->getCreateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * 创建一个使用 CreateTimeAware trait 的测试类
     */
    private function createTestEntity(): object
    {
        return new class {
            use CreateTimeAware;
        };
    }

    /**
     * 测试设置和获取 createTime - DateTimeImmutable
     */
    public function test_setAndGetCreateTime_withDateTimeImmutable(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new DateTimeImmutable('2024-01-15 10:30:45');

        $result = $entity->setCreateTime($dateTime);

        $this->assertSame($entity, $result, 'setCreateTime should return self for method chaining');
        $this->assertSame($dateTime, $entity->getCreateTime());
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreateTime());
        $this->assertEquals('2024-01-15 10:30:45', $entity->getCreateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * 测试设置和获取 createTime - null 值
     */
    public function test_setAndGetCreateTime_withNull(): void
    {
        $entity = $this->createTestEntity();

        $result = $entity->setCreateTime(null);

        $this->assertSame($entity, $result, 'setCreateTime should return self for method chaining');
        $this->assertNull($entity->getCreateTime());
    }

    /**
     * 测试初始状态 - createTime 应该为 null
     */
    public function test_initialState_shouldBeNull(): void
    {
        $entity = $this->createTestEntity();

        $this->assertNull($entity->getCreateTime(), 'Initial createTime should be null');
    }

    /**
     * 测试覆盖 createTime - 先设置一个值，再设置另一个值
     */
    public function test_setCreateTime_override(): void
    {
        $entity = $this->createTestEntity();
        $firstTime = new DateTimeImmutable('2024-01-15 10:30:45');
        $secondTime = new DateTimeImmutable('2024-01-16 15:45:30');

        $entity->setCreateTime($firstTime);
        $this->assertSame($firstTime, $entity->getCreateTime());

        $entity->setCreateTime($secondTime);
        $this->assertSame($secondTime, $entity->getCreateTime());
        $this->assertNotSame($firstTime, $entity->getCreateTime());
    }

    /**
     * 测试从有值设置为 null
     */
    public function test_setCreateTime_fromValueToNull(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new DateTimeImmutable('2024-01-15 10:30:45');

        $entity->setCreateTime($dateTime);
        $this->assertSame($dateTime, $entity->getCreateTime());

        $entity->setCreateTime(null);
        $this->assertNull($entity->getCreateTime());
    }

    /**
     * 测试从 null 设置为有值
     */
    public function test_setCreateTime_fromNullToValue(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new DateTimeImmutable('2024-01-15 10:30:45');

        $entity->setCreateTime(null);
        $this->assertNull($entity->getCreateTime());

        $entity->setCreateTime($dateTime);
        $this->assertSame($dateTime, $entity->getCreateTime());
    }

    /**
     * 测试边界时间值
     */
    public function test_boundaryTimeValues(): void
    {
        $entity = $this->createTestEntity();

        // 测试 Unix 纪元时间
        $epochTime = new DateTimeImmutable('@0');
        $entity->setCreateTime($epochTime);
        $this->assertSame($epochTime, $entity->getCreateTime());

        // 测试最大时间（年份 9999）
        $maxTime = new DateTimeImmutable('9999-12-31 23:59:59');
        $entity->setCreateTime($maxTime);
        $this->assertSame($maxTime, $entity->getCreateTime());

        // 测试最小时间（年份 1000）
        $minTime = new DateTimeImmutable('1000-01-01 00:00:00');
        $entity->setCreateTime($minTime);
        $this->assertSame($minTime, $entity->getCreateTime());
    }

    /**
     * 测试 DateTimeInterface 兼容性 - 确保支持任何实现 DateTimeInterface 的类
     */
    public function test_dateTimeInterface_compatibility(): void
    {
        $entity = $this->createTestEntity();

        // 测试 DateTime
        $dateTime = new DateTimeImmutable('2024-01-15 10:30:45');
        $entity->setCreateTime($dateTime);
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreateTime());

        // 测试 DateTimeImmutable
        $dateTimeImmutable = new DateTimeImmutable('2024-01-15 10:30:45');
        $entity->setCreateTime($dateTimeImmutable);
        $this->assertInstanceOf(DateTimeInterface::class, $entity->getCreateTime());
    }

    /**
     * 测试时间格式化 - 验证常见格式
     */
    public function test_timestampFormat_verification(): void
    {
        $entity = $this->createTestEntity();

        // 测试标准格式
        $standardTime = new DateTimeImmutable('2024-12-31 23:59:59');
        $entity->setCreateTime($standardTime);
        $this->assertEquals('2024-12-31 23:59:59', $entity->getCreateTime()->format('Y-m-d H:i:s'));

        // 测试边界格式
        $boundaryTime = new DateTimeImmutable('2024-01-01 00:00:01');
        $entity->setCreateTime($boundaryTime);
        $this->assertEquals('2024-01-01 00:00:01', $entity->getCreateTime()->format('Y-m-d H:i:s'));

        // 验证格式是否符合预期的正则表达式
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $entity->getCreateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * 测试方法链式调用
     */
    public function test_methodChaining(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new DateTimeImmutable('2024-01-15 10:30:45');

        // 测试 setCreateTime 返回 self 支持链式调用
        $result = $entity->setCreateTime($dateTime);
        $this->assertSame($entity, $result);

        // 测试可以连续调用
        $secondTime = new DateTime('2024-01-16 15:30:45');
        $finalResult = $entity->setCreateTime($dateTime)->setCreateTime($secondTime);
        $this->assertSame($entity, $finalResult);
        $this->assertSame($secondTime, $entity->getCreateTime());
    }

    /**
     * 测试内存引用 - 确保设置的对象引用被正确保持
     */
    public function test_objectReference_preservation(): void
    {
        $entity = $this->createTestEntity();
        $dateTime = new DateTimeImmutable('2024-01-15 10:30:45');

        $entity->setCreateTime($dateTime);

        // 修改原始对象，确保引用被保持
        $dateTime = $dateTime->modify('+1 day');

        $this->assertEquals('2024-01-16 10:30:45', $entity->getCreateTime()->format('Y-m-d H:i:s'));
        $this->assertSame($dateTime, $entity->getCreateTime());
    }

    /**
     * 测试多次设置 null 值
     */
    public function test_multipleNullSettings(): void
    {
        $entity = $this->createTestEntity();

        $entity->setCreateTime(null);
        $this->assertNull($entity->getCreateTime());

        $entity->setCreateTime(null);
        $this->assertNull($entity->getCreateTime());

        // 确保多次设置 null 不会有副作用
        $entity->setCreateTime(null);
        $this->assertNull($entity->getCreateTime());
    }
}
