<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Integration;

use Carbon\CarbonImmutable;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTimestampBundle\EventSubscriber\TimeListener;
use Tourze\DoctrineTimestampBundle\Tests\Fixtures\Entity\Article;
use Tourze\DoctrineTimestampBundle\Tests\Fixtures\Entity\Post;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;

class TimeListenerIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TimeListener $timeListener;

    protected static function createKernel(array $options = []): \Symfony\Component\HttpKernel\KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            DoctrineTimestampBundle::class => ['all' => true],
        ], [
            'Tourze\\DoctrineTimestampBundle\\Tests\\Fixtures\\Entity' => __DIR__ . '/../Fixtures/Entity',
        ]);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->timeListener = static::getContainer()->get(TimeListener::class);

        // 创建数据库架构
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = [
            $this->entityManager->getClassMetadata(Article::class),
            $this->entityManager->getClassMetadata(Post::class),
        ];

        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        // 固定测试时间
        CarbonImmutable::setTestNow(CarbonImmutable::create(2023, 6, 15, 10, 30, 0));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        $this->cleanDatabase();
        self::ensureKernelShutdown();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM article');
        $connection->executeStatement('DELETE FROM post');
    }

    public function test_timeListener_implementsEntityCheckerInterface(): void
    {
        $this->assertInstanceOf(
            \Tourze\DoctrineEntityCheckerBundle\Checker\EntityCheckerInterface::class,
            $this->timeListener
        );
    }

    public function test_prePersistEntity_setsCreateTimeForDateTimeType(): void
    {
        // Arrange
        $article = new Article();
        $article->setTitle('测试文章');
        $article->setContent('内容');

        // Act - 手动调用 prePersistEntity 方法
        $this->timeListener->prePersistEntity($this->entityManager, $article);

        // Assert
        $this->assertInstanceOf(DateTime::class, $article->getCreatedAt());
        $this->assertEquals('2023-06-15 10:30:00', $article->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertInstanceOf(DateTime::class, $article->getUpdatedAt());
        $this->assertEquals('2023-06-15 10:30:00', $article->getUpdatedAt()->format('Y-m-d H:i:s'));
    }

    public function test_prePersistEntity_setsCreateTimeForTimestampType(): void
    {
        // Arrange
        $post = new Post();
        $post->setTitle('测试帖子');
        $post->setContent('内容');

        // Act - 手动调用 prePersistEntity 方法
        $this->timeListener->prePersistEntity($this->entityManager, $post);

        // Assert
        $this->assertIsInt($post->getCreatedAt());
        $this->assertEquals(CarbonImmutable::getTestNow()->getTimestamp(), $post->getCreatedAt());
        $this->assertIsInt($post->getUpdatedAt());
        $this->assertEquals(CarbonImmutable::getTestNow()->getTimestamp(), $post->getUpdatedAt());
    }

    public function test_prePersistEntity_skipsIfValueAlreadySet(): void
    {
        // Arrange
        $article = new Article();
        $article->setTitle('测试文章');
        $article->setContent('内容');

        $presetTime = new DateTime('2020-01-01 12:00:00');
        $article->setCreatedAt(clone $presetTime);
        $article->setUpdatedAt(clone $presetTime);

        // Act - 手动调用 prePersistEntity 方法
        $this->timeListener->prePersistEntity($this->entityManager, $article);

        // Assert - 预设的时间应该被保留
        $this->assertEquals('2020-01-01 12:00:00', $article->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2020-01-01 12:00:00', $article->getUpdatedAt()->format('Y-m-d H:i:s'));
    }

    public function test_preUpdateEntity_setsUpdateTimeWhenFieldChanged(): void
    {
        // Arrange
        $article = new Article();
        $article->setTitle('测试文章');
        $article->setContent('内容');

        // 先持久化实体
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $originalCreateTime = clone $article->getCreatedAt();

        // 模拟时间流逝
        CarbonImmutable::setTestNow(CarbonImmutable::create(2023, 6, 15, 11, 0, 0));

        // 修改实体
        $article->setTitle('更新的标题');

        // 创建模拟的 PreUpdateEventArgs
        $changeSet = ['title' => ['测试文章', '更新的标题']];
        $eventArgs = $this->createMock(\Doctrine\ORM\Event\PreUpdateEventArgs::class);
        $eventArgs->method('hasChangedField')->willReturnCallback(function ($field) {
            return $field !== 'updatedAt'; // 只有updatedAt字段未被手动修改
        });

        // Act - 手动调用 preUpdateEntity 方法
        $this->timeListener->preUpdateEntity($this->entityManager, $article, $eventArgs);

        // Assert
        $this->assertEquals(
            $originalCreateTime->format('Y-m-d H:i:s'),
            $article->getCreatedAt()->format('Y-m-d H:i:s'),
            '创建时间不应被更改'
        );
        $this->assertEquals('2023-06-15 11:00:00', $article->getUpdatedAt()->format('Y-m-d H:i:s'));
    }

    public function test_preUpdateEntity_skipsIfFieldManuallyChanged(): void
    {
        // Arrange
        $article = new Article();
        $article->setTitle('测试文章');
        $article->setContent('内容');

        // 先持久化实体
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $updatedAt = $article->getUpdatedAt();
        $originalUpdateTime = null !== $updatedAt ? clone $updatedAt : null;

        // 模拟时间流逝
        CarbonImmutable::setTestNow(CarbonImmutable::create(2023, 6, 15, 11, 0, 0));

        // 手动设置更新时间
        $manualTime = new DateTime('2020-01-01 00:00:00');
        $article->setUpdatedAt($manualTime);
        $article->setTitle('更新的标题');

        // 创建模拟的 PreUpdateEventArgs
        $eventArgs = $this->createMock(\Doctrine\ORM\Event\PreUpdateEventArgs::class);
        $eventArgs->method('hasChangedField')->willReturnCallback(function ($field) {
            return $field === 'updatedAt'; // updatedAt字段已被手动修改
        });

        // Act - 手动调用 preUpdateEntity 方法
        $this->timeListener->preUpdateEntity($this->entityManager, $article, $eventArgs);

        // Assert - 手动设置的时间应该被保留
        $this->assertEquals('2020-01-01 00:00:00', $article->getUpdatedAt()->format('Y-m-d H:i:s'));
    }

    public function test_eventListener_triggersAutomaticallyOnPersist(): void
    {
        // Arrange
        $article = new Article();
        $article->setTitle('测试文章');
        $article->setContent('内容');

        // Act - 通过 EntityManager 触发真实的事件
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        // Assert - 事件监听器应该自动设置时间
        $this->assertInstanceOf(DateTime::class, $article->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $article->getUpdatedAt());
        $this->assertEquals('2023-06-15 10:30:00', $article->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-06-15 10:30:00', $article->getUpdatedAt()->format('Y-m-d H:i:s'));
    }

    public function test_eventListener_triggersAutomaticallyOnUpdate(): void
    {
        // Arrange
        $article = new Article();
        $article->setTitle('测试文章');
        $article->setContent('内容');

        // 先持久化
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $originalCreateTime = clone $article->getCreatedAt();

        // 模拟时间流逝
        CarbonImmutable::setTestNow(CarbonImmutable::create(2023, 6, 15, 11, 0, 0));

        // Act - 修改并保存，触发真实的 preUpdate 事件
        $article->setTitle('更新的标题');
        $this->entityManager->flush();

        // Assert
        $this->assertEquals(
            $originalCreateTime->format('Y-m-d H:i:s'),
            $article->getCreatedAt()->format('Y-m-d H:i:s'),
            '创建时间不应被更改'
        );
        $this->assertEquals('2023-06-15 11:00:00', $article->getUpdatedAt()->format('Y-m-d H:i:s'));
    }
}
