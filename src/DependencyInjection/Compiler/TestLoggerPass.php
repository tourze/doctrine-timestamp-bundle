<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\DependencyInjection\Compiler;

use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tourze\DoctrineTimestampBundle\EventSubscriber\TimeListener;

final class TestLoggerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $environment = $container->getParameter('kernel.environment');

        if ('test' === $environment) {
            $this->configureTestEnvironment($container);
        }
    }

    private function configureTestEnvironment(ContainerBuilder $container): void
    {
        $this->registerNullLogger($container);
        $this->configureTimeListener($container);
    }

    private function registerNullLogger(ContainerBuilder $container): void
    {
        $nullLoggerDef = new Definition(NullLogger::class);
        $container->setDefinition('doctrine_timestamp.null_logger', $nullLoggerDef);
    }

    private function configureTimeListener(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(TimeListener::class)) {
            $timeListenerDef = $container->getDefinition(TimeListener::class);
            $timeListenerDef->setPublic(true);
            $timeListenerDef->setArgument('$logger', new Reference('doctrine_timestamp.null_logger'));
        }
    }
}
