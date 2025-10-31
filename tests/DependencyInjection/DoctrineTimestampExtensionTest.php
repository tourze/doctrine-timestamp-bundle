<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineTimestampBundle\DependencyInjection\DoctrineTimestampExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineTimestampExtension::class)]
final class DoctrineTimestampExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testLoadInTestEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $extension = new DoctrineTimestampExtension();

        $extension->load([], $container);

        // 在测试环境中，Extension应该正常加载服务配置
        $this->assertTrue($container->hasDefinition('Tourze\DoctrineTimestampBundle\EventSubscriber\TimeListener'));
        $this->assertTrue($container->hasDefinition('doctrine-timestamp.property-accessor'));

        // NullLogger配置由CompilerPass处理，不在Extension中
        $this->assertFalse($container->hasDefinition('doctrine_timestamp.null_logger'));
    }
}
