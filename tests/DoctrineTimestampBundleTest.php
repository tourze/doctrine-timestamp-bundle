<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineTimestampBundle::class)]
#[RunTestsInSeparateProcesses]
final class DoctrineTimestampBundleTest extends AbstractBundleTestCase
{
}
