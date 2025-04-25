<?php

namespace Tourze\DoctrineTimestampBundle\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityCheckerBundle\DoctrineEntityCheckerBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;

class DoctrineTimestampBundleTest extends TestCase
{
    public function testBundleDependencies()
    {
        $dependencies = DoctrineTimestampBundle::getBundleDependencies();
        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey(DoctrineEntityCheckerBundle::class, $dependencies);
        $this->assertEquals(['all' => true], $dependencies[DoctrineEntityCheckerBundle::class]);
    }

    public function testBundleInstantiation()
    {
        $bundle = new DoctrineTimestampBundle();
        $this->assertInstanceOf(DoctrineTimestampBundle::class, $bundle);
    }
}
