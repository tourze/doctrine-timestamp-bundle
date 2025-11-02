<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Tests\EventSubscriber;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tourze\DoctrineTimestampBundle\EventSubscriber\TimeListener;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(TimeListener::class)]
#[RunTestsInSeparateProcesses]
final class TimeListenerTest extends AbstractIntegrationTestCase
{
    private PropertyAccessor $propertyAccessor;
    private LoggerInterface&MockObject $logger;
    private KernelInterface&MockObject $mockKernel;
    private TimeListener $timeListener;

    protected function onSetUp(): void
    {
        $this->propertyAccessor = new PropertyAccessor();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mockKernel = $this->createMock(KernelInterface::class);

        $this->timeListener = new TimeListener(
            $this->propertyAccessor,
            $this->logger,
            $this->mockKernel
        );
    }

    /**
     * 测试 TimeListener 可以正确实例化
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(TimeListener::class, $this->timeListener);
    }

    /**
     * 测试在生产环境中应该记录日志
     */
    public function testShouldLogInProductionEnvironment(): void
    {
        $this->mockKernel
            ->expects($this->once())
            ->method('getEnvironment')
            ->willReturn('prod');

        $reflection = new \ReflectionMethod($this->timeListener, 'shouldLog');
        $result = $reflection->invoke($this->timeListener);

        $this->assertTrue($result);
    }

    /**
     * 测试在测试环境中不应该记录日志
     */
    public function testShouldNotLogInTestEnvironment(): void
    {
        $this->mockKernel
            ->expects($this->once())
            ->method('getEnvironment')
            ->willReturn('test');

        $reflection = new \ReflectionMethod($this->timeListener, 'shouldLog');
        $result = $reflection->invoke($this->timeListener);

        $this->assertFalse($result);
    }

    /**
     * 测试没有内核信息时的环境检查
     */
    public function testShouldLogWithoutKernel(): void
    {
        $timeListener = new TimeListener(
            $this->propertyAccessor,
            $this->logger,
            null
        );

        $reflection = new \ReflectionMethod($timeListener, 'shouldLog');
        $result = $reflection->invoke($timeListener);

        // 在当前环境中应该返回 false，因为我们在 PHPUnit 环境中
        $this->assertFalse($result);
    }

    /**
     * 测试 prePersist 方法调用
     */
    public function testPrePersist(): void
    {
        $entity = new \stdClass();
        $objectManager = $this->createMock(\Doctrine\Persistence\ObjectManager::class);
        $metadata = $this->createMock(\Doctrine\Persistence\Mapping\ClassMetadata::class);
        $reflection = $this->createMock(\ReflectionClass::class);

        $objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($entity))
            ->willReturn($metadata);

        $metadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflection);

        $reflection->expects($this->once())
            ->method('getProperties')
            ->with(\ReflectionProperty::IS_PRIVATE)
            ->willReturn([]);

        // 由于 PrePersistEventArgs 是 final 类，我们直接测试 prePersistEntity 方法
        $this->timeListener->prePersistEntity($objectManager, $entity);
        $this->assertTrue(true); // 如果执行到这里说明没有异常
    }

    /**
     * 测试 preUpdate 方法调用
     */
    public function testPreUpdate(): void
    {
        $entity = new \stdClass();
        $objectManager = $this->createMock(\Doctrine\Persistence\ObjectManager::class);

        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn(['field' => ['old', 'new']]); // 模拟有变化
        $event->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($objectManager);
        $event->expects($this->once())
            ->method('getObject')
            ->willReturn($entity);

        $objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($entity))
            ->willReturn($this->createMock(\Doctrine\Persistence\Mapping\ClassMetadata::class));

        // 这个测试验证方法可以被调用而不抛出异常
        $this->timeListener->preUpdate($event);
        $this->assertTrue(true); // 如果执行到这里说明没有异常
    }

    /**
     * 测试 preUpdate 在没有变化时跳过处理
     */
    public function testPreUpdateSkipsWhenNoChanges(): void
    {
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn([]); // 模拟没有变化

        // getObjectManager 和 getObject 不应该被调用
        $event->expects($this->never())->method('getObjectManager');
        $event->expects($this->never())->method('getObject');

        $this->timeListener->preUpdate($event);
        $this->assertTrue(true); // 验证执行成功
    }

    /**
     * 测试 getValue 方法可以返回时间戳
     */
    public function testGetValueReturnsTimestamp(): void
    {
        $column = $this->createMock(\Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn::class);
        $column->type = \Tourze\DoctrineTimestampBundle\Enum\Types::timestamp;

        $reflection = new \ReflectionMethod($this->timeListener, 'getValue');
        $result = $reflection->invoke($this->timeListener, $column);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    /**
     * 测试 getValue 方法可以返回 DateTime 对象
     */
    public function testGetValueReturnsDateTime(): void
    {
        $column = $this->createMock(\Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn::class);
        $column->type = \Tourze\DoctrineTimestampBundle\Enum\Types::datetime;

        $reflection = new \ReflectionMethod($this->timeListener, 'getValue');
        $result = $reflection->invoke($this->timeListener, $column);

        $this->assertInstanceOf(\DateTimeInterface::class, $result);
    }

    /**
     * 测试 getDateTimeValue 方法处理不同的属性类型
     */
    public function testGetDateTimeValueWithDifferentPropertyTypes(): void
    {
        $time = \Carbon\CarbonImmutable::now();

        // 测试 DateTimeImmutable 属性
        $property = $this->createMockProperty(\DateTimeImmutable::class);
        $reflection = new \ReflectionMethod($this->timeListener, 'getDateTimeValue');
        $result = $reflection->invoke($this->timeListener, $time, $property);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);

        // 测试 DateTime 属性
        $property = $this->createMockProperty(\DateTime::class);
        $result = $reflection->invoke($this->timeListener, $time, $property);
        $this->assertInstanceOf(\DateTime::class, $result);

        // 测试 DateTimeInterface 属性
        $property = $this->createMockProperty(\DateTimeInterface::class);
        $result = $reflection->invoke($this->timeListener, $time, $property);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
    }

    /**
     * 创建 mock 属性对象
     */
    private function createMockProperty(string $typeName): \ReflectionProperty&MockObject
    {
        $property = $this->createMock(\ReflectionProperty::class);

        $type = $this->createMock(\ReflectionNamedType::class);
        $type->method('getName')->willReturn($typeName);

        $property->method('getType')->willReturn($type);

        return $property;
    }
}