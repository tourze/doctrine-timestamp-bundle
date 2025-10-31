<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Tests\EventSubscriber;

use BizUserBundle\Entity\BizUser;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineEntityCheckerBundle\Checker\EntityCheckerInterface;
use Tourze\DoctrineTimestampBundle\EventSubscriber\TimeListener;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;

/**
 * @internal
 */
#[CoversClass(TimeListener::class)]
#[RunTestsInSeparateProcesses]
final class TimeListenerTest extends AbstractEventSubscriberTestCase
{
    private TimeListener $timeListener;

    protected function onSetUp(): void
    {
        $this->timeListener = self::getService(TimeListener::class);
    }

    protected function onTearDown(): void
    {
        CarbonImmutable::setTestNow();
    }

    public function testImplementsEntityCheckerInterface(): void
    {
        $this->assertInstanceOf(
            EntityCheckerInterface::class,
            $this->timeListener
        );
    }

    public function testPrePersistEntity(): void
    {
        $entity = new BizUser();
        $entity->setUsername('testuser');
        $entity->setNickName('Test User');

        $objectManager = self::getEntityManager();

        $this->timeListener->prePersistEntity($objectManager, $entity);

        $this->assertInstanceOf(BizUser::class, $entity);
    }

    public function testPreUpdateEntity(): void
    {
        $entity = new BizUser();
        $entity->setUsername('testuser');
        $entity->setNickName('Test User');

        $objectManager = self::getEntityManager();

        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->method('hasChangedField')->willReturn(false);

        $this->timeListener->preUpdateEntity($objectManager, $entity, $args);

        $this->assertInstanceOf(BizUser::class, $entity);
    }

    public function testRetrieveTimestampArray(): void
    {
        $createTime = new \DateTimeImmutable('2023-05-15 12:00:00');
        $updateTime = new \DateTimeImmutable('2023-05-15 13:00:00');

        $this->timeListener->setCreateTime($createTime);
        $this->timeListener->setUpdateTime($updateTime);

        $result = $this->timeListener->retrieveTimestampArray();

        $this->assertArrayHasKey('createTime', $result);
        $this->assertArrayHasKey('updateTime', $result);
        $this->assertEquals('2023-05-15 12:00:00', $result['createTime']);
        $this->assertEquals('2023-05-15 13:00:00', $result['updateTime']);
    }

    public function testPreUpdateSkipsWhenNoChanges(): void
    {
        $entity = new BizUser();
        $entity->setUsername('testuser');
        $entity->setNickName('Test User');

        $objectManager = self::getEntityManager();

        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->method('getEntityChangeSet')->willReturn([]);
        $args->method('getObjectManager')->willReturn($objectManager);
        $args->method('getObject')->willReturn($entity);

        $this->timeListener->preUpdate($args);

        $this->assertInstanceOf(BizUser::class, $entity);
    }
}
