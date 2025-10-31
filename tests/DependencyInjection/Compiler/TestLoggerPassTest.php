<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tourze\DoctrineTimestampBundle\DependencyInjection\Compiler\TestLoggerPass;
use Tourze\DoctrineTimestampBundle\EventSubscriber\TimeListener;

/**
 * @internal
 */
#[CoversClass(TestLoggerPass::class)]
final class TestLoggerPassTest extends TestCase
{
    public function testProcessInTestEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        // 添加TimeListener服务定义
        $timeListenerDef = new Definition(TimeListener::class);
        $container->setDefinition(TimeListener::class, $timeListenerDef);

        $pass = new TestLoggerPass();
        $pass->process($container);

        // 验证NullLogger服务被注册
        $this->assertTrue($container->hasDefinition('doctrine_timestamp.null_logger'));
        $nullLoggerDef = $container->getDefinition('doctrine_timestamp.null_logger');
        $this->assertEquals(NullLogger::class, $nullLoggerDef->getClass());

        // 验证TimeListener服务配置被修改
        $listenerDef = $container->getDefinition(TimeListener::class);
        $this->assertTrue($listenerDef->isPublic());

        $arguments = $listenerDef->getArguments();
        $this->assertArrayHasKey('$logger', $arguments);
        $loggerRef = $arguments['$logger'];
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $loggerRef);
        $this->assertEquals('doctrine_timestamp.null_logger', (string) $loggerRef);
    }

    public function testProcessInProductionEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        // 添加TimeListener服务定义
        $timeListenerDef = new Definition(TimeListener::class);
        $container->setDefinition(TimeListener::class, $timeListenerDef);

        $pass = new TestLoggerPass();
        $pass->process($container);

        // 验证在生产环境下不做任何修改
        $this->assertFalse($container->hasDefinition('doctrine_timestamp.null_logger'));

        $listenerDef = $container->getDefinition(TimeListener::class);
        $this->assertFalse($listenerDef->isPublic());

        $arguments = $listenerDef->getArguments();
        $this->assertArrayNotHasKey('$logger', $arguments);
    }

    public function testProcessWithoutTimeListener(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $pass = new TestLoggerPass();
        $pass->process($container);

        // 验证NullLogger服务仍然被注册
        $this->assertTrue($container->hasDefinition('doctrine_timestamp.null_logger'));

        // 验证没有TimeListener服务
        $this->assertFalse($container->hasDefinition(TimeListener::class));
    }
}
