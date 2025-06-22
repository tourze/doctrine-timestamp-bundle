<?php

namespace Tourze\DoctrineTimestampBundle\EventSubscriber;

use Carbon\CarbonImmutable;
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ObjectManager;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tourze\DoctrineEntityCheckerBundle\Checker\EntityCheckerInterface;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Enum\Types;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[WithMonologChannel('doctrine-timestamp')]
#[AsDoctrineListener(event: Events::prePersist, priority: -99)]
#[AsDoctrineListener(event: Events::preUpdate, priority: -99)]
class TimeListener implements EntityCheckerInterface
{
    use TimestampableAware;
    public function __construct(
        #[Autowire(service: 'doctrine-timestamp.property-accessor')] private readonly PropertyAccessor $propertyAccessor,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->prePersistEntity($args->getObjectManager(), $args->getObject());
    }

    public function prePersistEntity(ObjectManager $objectManager, object $entity): void
    {
        $reflection = $objectManager->getClassMetadata(get_class($entity))->getReflectionClass();
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            // 处理 CreateTimeColumn 属性
            $createTimeColumns = $property->getAttributes(CreateTimeColumn::class);
            if (!empty($createTimeColumns)) {
                $this->setTimestampProperty($entity, $property, $createTimeColumns[0]->newInstance(), '创建时间');
                continue;
            }

            // 处理 UpdateTimeColumn 属性（在创建时也需要设置）
            $updateTimeColumns = $property->getAttributes(UpdateTimeColumn::class);
            if (!empty($updateTimeColumns)) {
                $this->setTimestampProperty($entity, $property, $updateTimeColumns[0]->newInstance(), '创建时间');
            }
        }
    }

    private function setTimestampProperty(object $entity, \ReflectionProperty $property, CreateTimeColumn|UpdateTimeColumn $column, string $logType): void
    {
        try {
            // 如果已经有了时间，那么要跳过
            $oldValue = $this->propertyAccessor->getValue($entity, $property->getName());
            if (null !== $oldValue) {
                return;
            }
        } catch (UninitializedPropertyException $exception) {
            // The property "XXX\Entity\XXX::$createTime" is not readable because it is typed "DateTimeInterface". You should initialize it or declare a default value instead.
            // 跳过这个错误
        }

        // 如果无法写入，则跳过
        if (!$this->propertyAccessor->isWritable($entity, $property->getName())) {
            $this->logger?->warning($logType . '无法写入', [
                'className' => get_class($entity),
                'entity' => $entity,
                'property' => $property,
            ]);
            return;
        }

        // 获取属性的实际类型以决定返回什么类型的时间对象
        $time = $this->getValue($column, $property);
        $this->logger?->debug('设置' . $logType, [
            'className' => get_class($entity),
            'entity' => $entity,
            'time' => $time,
            'property' => $property,
            'columnType' => $column->type,
        ]);

        $this->propertyAccessor->setValue($entity, $property->getName(), $time);

        // 验证设置后的值
        $newValue = $this->propertyAccessor->getValue($entity, $property->getName());
        $this->logger?->debug('验证' . $logType . '设置结果', [
            'className' => get_class($entity),
            'property' => $property->getName(),
            'setValue' => $time,
            'getValue' => $newValue,
            'success' => $newValue !== null
        ]);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        // 如果数据都没变化，那我们也没必要更新时间
        if (empty($args->getEntityChangeSet())) {
            return;
        }
        $this->preUpdateEntity($args->getObjectManager(), $args->getObject(), $args);
    }

    public function preUpdateEntity(ObjectManager $objectManager, object $entity, PreUpdateEventArgs $eventArgs): void
    {
        $reflection = $objectManager->getClassMetadata(get_class($entity))->getReflectionClass();
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            $updateTimeColumns = $property->getAttributes(UpdateTimeColumn::class);
            if (empty($updateTimeColumns)) {
                continue;
            }

            // 如果已经主动改过了，那我们应该不用继续修改了
            if ($eventArgs->hasChangedField($property->getName())) {
                continue;
            }

            // 如果无法写入，则跳过
            if (!$this->propertyAccessor->isWritable($entity, $property->getName())) {
                $this->logger?->warning('更新时间无法写入', [
                    'className' => get_class($entity),
                    'entity' => $entity,
                    'property' => $property,
                ]);
                continue;
            }

            $updateTimeColumn = $updateTimeColumns[0]->newInstance();
            $time = $this->getValue($updateTimeColumn, $property);
            $this->logger?->debug('设置更新时间', [
                'className' => get_class($entity),
                'entity' => $entity,
                'time' => $time,
                'updateTimeColumn' => $updateTimeColumn->type,
            ]);

            $this->propertyAccessor->setValue($entity, $property->getName(), $time);
        }
    }

    private function getValue(CreateTimeColumn|UpdateTimeColumn $column, ?\ReflectionProperty $property = null): DateTime|DateTimeImmutable|int
    {
        $time = CarbonImmutable::now();
        if (Types::timestamp === $column->type) {
            return $time->getTimestamp();
        }

        // 如果有属性信息，检查属性的实际类型
        if (null !== $property) {
            $propertyType = $property->getType();
            if ($propertyType instanceof \ReflectionNamedType) {
                $typeName = $propertyType->getName();
                if (DateTimeImmutable::class === $typeName) {
                    return $time->toDateTimeImmutable();
                }
                if (DateTime::class === $typeName) {
                    return $time->toDateTime();
                }
                // 对于 DateTimeInterface，根据 Doctrine 字段类型推断
                if ($typeName === \DateTimeInterface::class) {
                    // 这里可以添加更多逻辑来根据 Doctrine 注解推断
                    return $time->toDateTimeImmutable();
                }
            }
        }

        // 默认返回 DateTimeImmutable 以兼容现代 Doctrine 实践
        return $time->toDateTimeImmutable();
    }
}
