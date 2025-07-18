<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTimestampBundle\EventSubscriber\TimeListener;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;

class TimeListenerServiceTest extends KernelTestCase
{
    protected static function createKernel(array $options = []): \Symfony\Component\HttpKernel\KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            DoctrineTimestampBundle::class => ['all' => true],
        ]);
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected function tearDown(): void
    {
        self::ensureKernelShutdown();
        parent::tearDown();
    }

    public function test_timeListenerService_existsAndIsConfigured(): void
    {
        $container = static::getContainer();

        // 验证服务是否存在
        $this->assertTrue($container->has(TimeListener::class));

        // 获取服务实例
        $timeListener = $container->get(TimeListener::class);
        $this->assertInstanceOf(TimeListener::class, $timeListener);

        // 验证服务是否被正确配置
        $reflection = new \ReflectionClass(TimeListener::class);

        // 验证依赖注入：PropertyAccessor
        $propertyAccessorProp = $reflection->getProperty('propertyAccessor');
        $propertyAccessorProp->setAccessible(true);
        $this->assertInstanceOf(PropertyAccessor::class, $propertyAccessorProp->getValue($timeListener));

        // 验证依赖注入：Logger (可能是空的)
        $loggerProp = $reflection->getProperty('logger');
        $loggerProp->setAccessible(true);
        $loggerValue = $loggerProp->getValue($timeListener);
        $this->assertTrue($loggerValue === null || $loggerValue instanceof LoggerInterface);
    }

    public function test_timeListener_hasCorrectDoctrineAttributes(): void
    {
        $reflection = new \ReflectionClass(TimeListener::class);
        $attributes = $reflection->getAttributes(AsDoctrineListener::class);

        // 至少应有prePersist和preUpdate事件
        $this->assertGreaterThanOrEqual(2, count($attributes));

        $eventTypes = [];
        $priorities = [];

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $eventTypes[] = $instance->event;
            $priorities[] = $instance->priority;
        }

        // 验证事件类型
        $this->assertContains(Events::prePersist, $eventTypes);
        $this->assertContains(Events::preUpdate, $eventTypes);

        // 验证优先级
        $this->assertContains(-99, $priorities);
    }

    public function test_propertyAccessorService_existsAndIsConfigured(): void
    {
        $container = static::getContainer();

        // 验证服务是否存在
        $this->assertTrue($container->has('doctrine-timestamp.property-accessor'));

        // 获取服务实例
        $propertyAccessor = $container->get('doctrine-timestamp.property-accessor');
        $this->assertInstanceOf(PropertyAccessor::class, $propertyAccessor);
    }
}
