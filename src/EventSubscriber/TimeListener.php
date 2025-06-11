<?php

namespace Tourze\DoctrineTimestampBundle\EventSubscriber;

use Carbon\Carbon;
use DateTime;
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

#[WithMonologChannel('doctrine-timestamp')]
#[AsDoctrineListener(event: Events::prePersist, priority: -99)]
#[AsDoctrineListener(event: Events::preUpdate, priority: -99)]
class TimeListener implements EntityCheckerInterface
{
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
        $reflection = $objectManager->getClassMetadata($entity::class)->getReflectionClass();
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
            if ($oldValue) {
                return;
            }
        } catch (UninitializedPropertyException $exception) {
            // The property "XXX\Entity\XXX::$createTime" is not readable because it is typed "DateTimeInterface". You should initialize it or declare a default value instead.
            // 跳过这个错误
        }

        // 如果无法写入，则跳过
        if (!$this->propertyAccessor->isWritable($entity, $property->getName())) {
            $this->logger?->warning($logType . '无法写入', [
                'className' => $entity::class,
                'entity' => $entity,
                'property' => $property,
            ]);
            return;
        }

        $time = $this->getValue($column);
        $this->logger?->debug('设置' . $logType, [
            'className' => $entity::class,
            'entity' => $entity,
            'time' => $time,
            'property' => $property,
            'columnType' => $column->type,
        ]);

        $this->propertyAccessor->setValue($entity, $property->getName(), $time);

        // 验证设置后的值
        $newValue = $this->propertyAccessor->getValue($entity, $property->getName());
        $this->logger?->debug('验证' . $logType . '设置结果', [
            'className' => $entity::class,
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
        $reflection = $objectManager->getClassMetadata($entity::class)->getReflectionClass();
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
                    'className' => $entity::class,
                    'entity' => $entity,
                    'property' => $property,
                ]);
                continue;
            }

            $updateTimeColumn = $updateTimeColumns[0]->newInstance();
            $time = $this->getValue($updateTimeColumn);
            $this->logger?->debug('设置更新时间', [
                'className' => $entity::class,
                'entity' => $entity,
                'time' => $time,
                'updateTimeColumn' => $updateTimeColumn->type,
            ]);

            $this->propertyAccessor->setValue($entity, $property->getName(), $time);
        }
    }

    private function getValue(CreateTimeColumn|UpdateTimeColumn $column): DateTime|int
    {
        $time = Carbon::now();
        if (Types::timestamp === $column->type) {
            return $time->getTimestamp();
        }
        return $time;
    }
}
