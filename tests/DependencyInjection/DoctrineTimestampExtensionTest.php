<?php

namespace Tourze\DoctrineTimestampBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineTimestampBundle\DependencyInjection\DoctrineTimestampExtension;

class DoctrineTimestampExtensionTest extends TestCase
{
    public function testLoad()
    {
        $container = new ContainerBuilder();
        $extension = new DoctrineTimestampExtension();

        $extension->load([], $container);

        // 检查服务定义
        $this->assertTrue($container->hasDefinition('Tourze\DoctrineTimestampBundle\EventSubscriber\TimeListener'));
        $this->assertTrue($container->hasDefinition('doctrine-timestamp.property-accessor'));

        // 检查property accessor服务
        $propertyAccessorDef = $container->getDefinition('doctrine-timestamp.property-accessor');
        $this->assertEquals('Symfony\Component\PropertyAccess\PropertyAccessor', $propertyAccessorDef->getClass());
        $this->assertTrue($propertyAccessorDef->isAutowired());
        $this->assertTrue($propertyAccessorDef->isAutoconfigured());

        // 检查TimeListener服务配置
        $listenerDef = $container->getDefinition('Tourze\DoctrineTimestampBundle\EventSubscriber\TimeListener');
        $this->assertTrue($listenerDef->isAutowired());
        $this->assertTrue($listenerDef->isAutoconfigured());
    }
}
