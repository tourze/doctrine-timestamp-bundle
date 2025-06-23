<?php

namespace Tourze\DoctrineTimestampBundle\Tests\EventSubscriber;

use Carbon\CarbonImmutable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tourze\DoctrineTimestampBundle\EventSubscriber\TimeListener;
use Tourze\DoctrineTimestampBundle\Tests\Fixtures\Entity\TestEntity;

/**
 * TimeListener 单元测试
 */
class TimeListenerUnitTest extends TestCase
{
    private PropertyAccessor|MockObject $propertyAccessor;
    private LoggerInterface|MockObject $logger;
    private TimeListener $timeListener;

    protected function setUp(): void
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessor::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->timeListener = new TimeListener(
            $this->propertyAccessor,
            $this->logger
        );
    }

    /**
     * 测试 prePersist 事件处理
     */
    public function testPrePersist_setsCreateTimeWhenNotExists(): void
    {
        // 固定测试时间
        CarbonImmutable::setTestNow(CarbonImmutable::create(2023, 6, 15, 10, 30, 0));

        // 创建测试实体
        $entity = new TestEntity();

        // Mock EntityManager 和 ClassMetadata
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $reflectionClass = new ReflectionClass(TestEntity::class);

        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($classMetadata);

        $classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflectionClass);

        // Mock PropertyAccessor 行为 - TestEntity有两个字段，每个字段调用getValue两次（检查+验证）
        $this->propertyAccessor->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturnCallback(function ($entity, $propertyName) {
                if ($propertyName === 'createdAt' || $propertyName === 'updatedAt') {
                    static $callCount = [];
                    $callCount[$propertyName] = ($callCount[$propertyName] ?? 0) + 1;
                    if ($callCount[$propertyName] === 1) {
                        return null; // 第一次检查返回null
                    } else {
                        return CarbonImmutable::create(2023, 6, 15, 10, 30, 0); // 验证时返回设置的时间
                    }
                }
                return null;
            });

        $this->propertyAccessor->expects($this->atLeast(2))
            ->method('isWritable')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->atLeastOnce())
            ->method('setValue')
            ->with($entity, $this->logicalOr('createdAt', 'updatedAt', 'createTime', 'updateTime'), $this->callback(function ($value) {
                return $value instanceof DateTime || $value instanceof \DateTimeImmutable;
            }));

        // 创建 PrePersistEventArgs
        $args = new PrePersistEventArgs($entity, $entityManager);

        // 执行测试
        $this->timeListener->prePersist($args);

        // 重置测试时间
        CarbonImmutable::setTestNow();
    }

    /**
     * 测试 prePersist 当时间已存在时跳过
     */
    public function testPrePersist_skipsWhenTimeAlreadyExists(): void
    {
        $entity = new TestEntity();
        $existingTime = new DateTime('2020-01-01 00:00:00');

        // Mock EntityManager 和 ClassMetadata
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $reflectionClass = new ReflectionClass(TestEntity::class);

        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflectionClass);

        // Mock PropertyAccessor 返回已存在的时间 - TestEntity有两个字段，都已有值
        $this->propertyAccessor->expects($this->atLeast(2))
            ->method('getValue')
            ->willReturn($existingTime);

        // 不应该调用 setValue
        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $args = new PrePersistEventArgs($entity, $entityManager);
        $this->timeListener->prePersist($args);
    }

    /**
     * 测试 preUpdate 事件处理
     */
    public function testPreUpdate_setsUpdateTimeWhenChanged(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2023, 6, 15, 11, 0, 0));

        $entity = new TestEntity();

        // Mock EntityManager 和 ClassMetadata
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $reflectionClass = new ReflectionClass(TestEntity::class);

        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflectionClass);

        // Mock PreUpdateEventArgs
        $changeSet = ['title' => ['旧标题', '新标题']];
        $preUpdateEventArgs = $this->createMock(PreUpdateEventArgs::class);
        $preUpdateEventArgs->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn($changeSet);
        $preUpdateEventArgs->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($entityManager);
        $preUpdateEventArgs->expects($this->once())
            ->method('getObject')
            ->willReturn($entity);
        $preUpdateEventArgs->expects($this->atLeastOnce())
            ->method('hasChangedField')
            ->with($this->logicalOr('updateTime', 'updatedAt'))
            ->willReturn(false); // 没有手动修改更新时间字段

        // Mock PropertyAccessor 行为
        $this->propertyAccessor->expects($this->atLeastOnce())
            ->method('isWritable')
            ->with($entity, $this->logicalOr('updateTime', 'updatedAt'))
            ->willReturn(true);

        $this->propertyAccessor->expects($this->atLeastOnce())
            ->method('setValue')
            ->with($entity, $this->logicalOr('updateTime', 'updatedAt'), $this->callback(function ($value) {
                return $value instanceof \DateTimeImmutable || $value instanceof DateTime;
            }));

        $this->timeListener->preUpdate($preUpdateEventArgs);

        CarbonImmutable::setTestNow();
        
        // 添加断言以确认测试执行成功
        $this->assertTrue(true);
    }

    /**
     * 测试 preUpdate 当没有变化时跳过
     */
    public function testPreUpdate_skipsWhenNoChanges(): void
    {
        $entity = new TestEntity();

        // Mock PreUpdateEventArgs - 没有变化
        $preUpdateEventArgs = $this->createMock(PreUpdateEventArgs::class);
        $preUpdateEventArgs->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn([]); // 空的变化集

        // 不应该调用其他方法
        $preUpdateEventArgs->expects($this->never())
            ->method('getObjectManager');

        $this->timeListener->preUpdate($preUpdateEventArgs);
    }

    /**
     * 测试 PropertyAccessor 不可写时记录警告
     */
    public function testPrePersist_logsWarningWhenNotWritable(): void
    {
        $entity = new TestEntity();

        // Mock EntityManager 和 ClassMetadata
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $reflectionClass = new ReflectionClass(TestEntity::class);

        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflectionClass);

        // Mock PropertyAccessor 行为 - TestEntity有两个字段
        $this->propertyAccessor->expects($this->atLeast(2))
            ->method('getValue')
            ->willReturn(null);

        $this->propertyAccessor->expects($this->atLeast(2))
            ->method('isWritable')
            ->willReturn(false); // 不可写

        // 期望记录警告日志 - 可能有多个字段会记录警告（包括trait中的字段）
        $this->logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with($this->stringContains('无法写入'), $this->isType('array'));

        // 不应该调用 setValue
        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        $args = new PrePersistEventArgs($entity, $entityManager);
        $this->timeListener->prePersist($args);
    }
}
