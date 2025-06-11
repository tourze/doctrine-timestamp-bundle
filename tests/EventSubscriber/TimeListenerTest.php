<?php

namespace Tourze\DoctrineTimestampBundle\Tests\EventSubscriber;

use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tourze\DoctrineTimestampBundle\EventSubscriber\TimeListener;
use Tourze\DoctrineTimestampBundle\Tests\Fixtures\Entity\MixedTypesEntity;
use Tourze\DoctrineTimestampBundle\Tests\Fixtures\Entity\NoAttributesEntity;
use Tourze\DoctrineTimestampBundle\Tests\Fixtures\Entity\PresetValuesEntity;
use Tourze\DoctrineTimestampBundle\Tests\Fixtures\Entity\TestEntity;
use Tourze\DoctrineTimestampBundle\Tests\Fixtures\Entity\TimestampEntity;

class TimeListenerTest extends TestCase
{
    private PropertyAccessor|MockObject $propertyAccessor;
    private TimeListener $timeListener;
    private ObjectManager|MockObject $objectManager;
    private ClassMetadata|MockObject $classMetadata;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();

        // 固定当前时间，以便于测试断言
        Carbon::setTestNow(Carbon::create(2023, 5, 15, 12, 0, 0));

        // 模拟PropertyAccessor
        $this->propertyAccessor = $this->createMock(PropertyAccessor::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->timeListener = new TimeListener($this->propertyAccessor, $this->logger);

        // 创建ObjectManager和ClassMetadata模拟
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @dataProvider doctrineAttributesProvider
     */
    public function testDoctrineAttributes()
    {
        $reflection = new ReflectionClass(TimeListener::class);
        $attributes = $reflection->getAttributes();

        $this->assertGreaterThanOrEqual(2, count($attributes));

        $foundPrePersist = false;
        $foundPreUpdate = false;

        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener') {
                $args = $attribute->getArguments();
                if (isset($args['event']) && $args['event'] === Events::prePersist) {
                    $foundPrePersist = true;
                    $this->assertEquals(-99, $args['priority']);
                }
                if (isset($args['event']) && $args['event'] === Events::preUpdate) {
                    $foundPreUpdate = true;
                    $this->assertEquals(-99, $args['priority']);
                }
            }
        }

        $this->assertTrue($foundPrePersist, 'AsDoctrineListener attribute for prePersist not found');
        $this->assertTrue($foundPreUpdate, 'AsDoctrineListener attribute for preUpdate not found');
    }

    public function testPrePersistWithDateTime()
    {
        $entity = new TestEntity();

        // 设置模拟的反射类
        $reflection = new ReflectionClass(TestEntity::class);
        $this->classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflection);

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($this->classMetadata);

        // 设置属性访问器行为 - TestEntity有2个时间字段，每个字段调用getValue至少2次（检查旧值+验证新值）
        $this->propertyAccessor->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturn(null);

        // 添加 isWritable 期望 - 两个字段都需要检查
        $this->propertyAccessor->expects($this->exactly(2))
            ->method('isWritable')
            ->willReturn(true);

        // setValue会被调用两次，一次为createdAt，一次为updatedAt
        $this->propertyAccessor->expects($this->exactly(2))
            ->method('setValue')
            ->with(
                $this->identicalTo($entity),
                $this->logicalOr(
                    $this->equalTo('createdAt'),
                    $this->equalTo('updatedAt')
                ),
                $this->callback(function ($value) {
                    return $value instanceof DateTime && $value->format('Y-m-d H:i:s') === '2023-05-15 12:00:00';
                })
            );

        // 模拟日志记录 - TestEntity有两个时间字段，每个字段2次debug调用（设置+验证），总共4次
        $this->logger->expects($this->exactly(4))
            ->method('debug')
            ->with(
                $this->logicalOr(
                    $this->equalTo('设置创建时间'),
                    $this->equalTo('验证创建时间设置结果')
                ),
                $this->anything()
            );

        // 执行测试
        $args = new PrePersistEventArgs($entity, $this->objectManager);
        $this->timeListener->prePersist($args);
    }

    public function testPrePersistWithTimestamp()
    {
        $entity = new TimestampEntity();

        // 设置模拟的反射类
        $reflection = new ReflectionClass(TimestampEntity::class);
        $this->classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflection);

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(TimestampEntity::class)
            ->willReturn($this->classMetadata);

        // 设置属性访问器行为
        $this->propertyAccessor->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturn(null);

        // 添加 isWritable 期望 - TimestampEntity有两个时间戳字段
        $this->propertyAccessor->expects($this->exactly(2))
            ->method('isWritable')
            ->willReturn(true);

        $expectedTimestamp = Carbon::now()->getTimestamp();
        $this->propertyAccessor->expects($this->exactly(2))
            ->method('setValue')
            ->with(
                $this->identicalTo($entity),
                $this->logicalOr(
                    $this->equalTo('createdAt'),
                    $this->equalTo('updatedAt')
                ),
                $this->equalTo($expectedTimestamp)
            );

        // 执行测试    
        $args = new PrePersistEventArgs($entity, $this->objectManager);
        $this->timeListener->prePersist($args);
    }

    public function testPrePersistWithPresetValues()
    {
        $entity = new PresetValuesEntity();
        $originalDate = new DateTime('2023-01-01 00:00:00');

        // 设置模拟的反射类
        $reflection = new ReflectionClass(PresetValuesEntity::class);
        $this->classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflection);

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(PresetValuesEntity::class)
            ->willReturn($this->classMetadata);

        // 设置属性访问器行为 - 返回已存在的值
        $this->propertyAccessor->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturn($originalDate);

        // setValue不应被调用
        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        // 执行测试
        $args = new PrePersistEventArgs($entity, $this->objectManager);
        $this->timeListener->prePersist($args);
    }

    public function testPrePersistWithMixedTypes()
    {
        $entity = new MixedTypesEntity();

        // 设置模拟的反射类
        $reflection = new ReflectionClass(MixedTypesEntity::class);
        $this->classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflection);

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(MixedTypesEntity::class)
            ->willReturn($this->classMetadata);

        // 设置属性访问器行为
        $this->propertyAccessor->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturn(null);

        // 添加 isWritable 期望 - MixedTypesEntity有两个时间戳字段
        $this->propertyAccessor->expects($this->exactly(2))
            ->method('isWritable')
            ->willReturn(true);

        $this->propertyAccessor->expects($this->exactly(2))
            ->method('setValue')
            ->with(
                $this->identicalTo($entity),
                $this->logicalOr(
                    $this->equalTo('createdAt'),
                    $this->equalTo('updatedAt')
                ),
                $this->callback(function ($value) {
                    return ($value instanceof DateTime) || is_int($value);
                })
            );

        // 执行测试    
        $args = new PrePersistEventArgs($entity, $this->objectManager);
        $this->timeListener->prePersist($args);
    }

    public function testPrePersistWithNoAttributes()
    {
        $entity = new NoAttributesEntity();

        // 设置模拟的反射类
        $reflection = new ReflectionClass(NoAttributesEntity::class);
        $this->classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflection);

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(NoAttributesEntity::class)
            ->willReturn($this->classMetadata);

        // 模拟日志 - 不应被调用
        $this->logger->expects($this->never())
            ->method('debug');

        // setValue不应被调用
        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        // 执行测试
        $args = new PrePersistEventArgs($entity, $this->objectManager);
        $this->timeListener->prePersist($args);
    }

    public function testPreUpdateWithChanges()
    {
        $entity = new TestEntity();

        // 设置模拟的反射类
        $reflection = new ReflectionClass(TestEntity::class);
        $this->classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflection);

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($this->classMetadata);

        // 模拟事件参数
        $changeSet = ['someField' => ['oldValue', 'newValue']];
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn($changeSet);
        $args->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);
        $args->expects($this->once())
            ->method('getObject')
            ->willReturn($entity);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('updatedAt')
            ->willReturn(false);

        // 设置属性访问器行为
        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with(
                $this->identicalTo($entity),
                $this->equalTo('updatedAt'),
                $this->callback(function ($value) {
                    return $value instanceof DateTime && $value->format('Y-m-d H:i:s') === '2023-05-15 12:00:00';
                })
            );

        // 添加 isWritable 期望
        $this->propertyAccessor->expects($this->once())
            ->method('isWritable')
            ->with($this->identicalTo($entity), 'updatedAt')
            ->willReturn(true);

        // 模拟日志记录
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('设置更新时间', $this->anything());

        // 执行测试
        $this->timeListener->preUpdate($args);
    }

    public function testPreUpdateWithNoChanges()
    {
        $entity = new TestEntity();

        // 模拟事件参数
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn([]);
        $args->expects($this->never())
            ->method('getObjectManager');
        $args->expects($this->never())
            ->method('getObject');

        // 执行测试
        $this->timeListener->preUpdate($args);

        // 不应修改实体，因为没有变化
    }

    public function testPreUpdateWithManualChange()
    {
        $entity = new TestEntity();

        // 设置模拟的反射类
        $reflection = new ReflectionClass(TestEntity::class);
        $this->classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflection);

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($this->classMetadata);

        // 模拟事件参数
        $changeSet = ['someField' => ['oldValue', 'newValue']];
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn($changeSet);
        $args->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);
        $args->expects($this->once())
            ->method('getObject')
            ->willReturn($entity);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('updatedAt')
            ->willReturn(true);

        // 模拟日志记录 - 不应该被调用
        $this->logger->expects($this->never())
            ->method('debug');

        // setValue不应被调用，因为已手动改变
        $this->propertyAccessor->expects($this->never())
            ->method('setValue');

        // 执行测试
        $this->timeListener->preUpdate($args);
    }

    public function testPreUpdateWithTimestampType()
    {
        $entity = new TimestampEntity();

        // 设置模拟的反射类
        $reflection = new ReflectionClass(TimestampEntity::class);
        $this->classMetadata->expects($this->once())
            ->method('getReflectionClass')
            ->willReturn($reflection);

        $this->objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(TimestampEntity::class)
            ->willReturn($this->classMetadata);

        // 模拟事件参数
        $changeSet = ['someField' => ['oldValue', 'newValue']];
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn($changeSet);
        $args->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);
        $args->expects($this->once())
            ->method('getObject')
            ->willReturn($entity);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('updatedAt')
            ->willReturn(false);

        // 设置属性访问器行为
        $expectedTimestamp = Carbon::now()->getTimestamp();
        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with(
                $this->identicalTo($entity),
                $this->equalTo('updatedAt'),
                $this->equalTo($expectedTimestamp)
            );

        // 添加 isWritable 期望
        $this->propertyAccessor->expects($this->once())
            ->method('isWritable')
            ->with($this->identicalTo($entity), 'updatedAt')
            ->willReturn(true);

        // 执行测试
        $this->timeListener->preUpdate($args);
    }

    private function doctrineAttributesProvider(): array
    {
        return [['dummy']]; // 只是为了启用数据提供者
    }
}
