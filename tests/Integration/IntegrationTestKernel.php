<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Tourze\DoctrineEntityCheckerBundle\DoctrineEntityCheckerBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;

class IntegrationTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct()
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new DoctrineEntityCheckerBundle(),
            new DoctrineTimestampBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'test' => true,
            'secret' => 'test',
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => [
                'log' => true,
            ],
        ]);

        $container->loadFromExtension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'path' => '%kernel.cache_dir%/test.db',
                'charset' => 'UTF8',
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'auto_mapping' => true,
                'mappings' => [
                    'TestEntities' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => __DIR__ . '/Entity',
                        'prefix' => 'Tourze\DoctrineTimestampBundle\Tests\Integration\Entity',
                    ],
                ],
            ],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // 路由配置（如果需要的话）
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/test_doctrine_timestamp_bundle/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/test_doctrine_timestamp_bundle/logs';
    }
}
