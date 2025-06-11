<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Integration;

use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTimestampBundle\Tests\Integration\Entity\Article;
use Tourze\DoctrineTimestampBundle\Tests\Integration\Entity\Post;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;

class DoctrineTimestampIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected static function createKernel(array $options = []): KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            DoctrineTimestampBundle::class => ['all' => true],
        ], [
            'Tourze\\DoctrineTimestampBundle\\Tests\\Integration\\Entity' => __DIR__ . '/Entity',
        ]);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // 创建数据库架构
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = [
            $this->entityManager->getClassMetadata(Article::class),
            $this->entityManager->getClassMetadata(Post::class),
        ];

        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        // 固定测试时间
        Carbon::setTestNow(Carbon::create(2023, 6, 15, 10, 30, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
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

    public function test_createDateTime_setsTimestampAutomatically(): void
    {
        // Arrange
        $article = new Article();
        $article->setTitle('测试文章');
        $article->setContent('这是一篇测试文章内容');

        // Act
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        // Assert
        $this->assertInstanceOf(DateTime::class, $article->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $article->getUpdatedAt());

        // 验证时间是当前测试时间
        $this->assertEquals('2023-06-15 10:30:00', $article->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-06-15 10:30:00', $article->getUpdatedAt()->format('Y-m-d H:i:s'));
    }

    public function test_createTimestamp_setsTimestampAutomatically(): void
    {
        // Arrange
        $post = new Post();
        $post->setTitle('测试帖子');
        $post->setContent('这是一篇测试帖子内容');

        // Act
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        // Assert
        $this->assertIsInt($post->getCreatedAt());
        $this->assertIsInt($post->getUpdatedAt());

        // 验证时间戳是当前测试时间戳
        $expectedTimestamp = Carbon::getTestNow()->getTimestamp();
        $this->assertEquals($expectedTimestamp, $post->getCreatedAt());
        $this->assertEquals($expectedTimestamp, $post->getUpdatedAt());
    }

    public function test_updateDateTime_onlyUpdatesUpdateTime(): void
    {
        // Arrange
        $article = new Article();
        $article->setTitle('测试文章');
        $article->setContent('这是一篇测试文章内容');

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $originalCreateTime = clone $article->getCreatedAt();

        // 模拟时间流逝
        Carbon::setTestNow(Carbon::create(2023, 6, 15, 11, 0, 0));

        // Act - 修改文章内容触发更新
        $article->setTitle('更新的标题');
        $this->entityManager->flush();

        // Assert
        $this->assertEquals(
            $originalCreateTime->format('Y-m-d H:i:s'),
            $article->getCreatedAt()->format('Y-m-d H:i:s'),
            '创建时间不应被更改'
        );

        $this->assertEquals(
            '2023-06-15 11:00:00',
            $article->getUpdatedAt()->format('Y-m-d H:i:s'),
            '更新时间应该是新的时间'
        );
    }

    public function test_updateTimestamp_onlyUpdatesUpdateTime(): void
    {
        // Arrange
        $post = new Post();
        $post->setTitle('测试帖子');
        $post->setContent('这是一篇测试帖子内容');

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $originalCreateTimestamp = $post->getCreatedAt();

        // 模拟时间流逝
        Carbon::setTestNow(Carbon::create(2023, 6, 15, 11, 0, 0));

        // Act - 修改帖子内容触发更新
        $post->setTitle('更新的帖子标题');
        $this->entityManager->flush();

        // Assert
        $this->assertEquals($originalCreateTimestamp, $post->getCreatedAt(), '创建时间戳不应被更改');

        $expectedNewTimestamp = Carbon::getTestNow()->getTimestamp();
        $this->assertEquals($expectedNewTimestamp, $post->getUpdatedAt(), '更新时间戳应该是新的时间戳');
    }

    public function test_manualUpdate_preservesManuallySetTime(): void
    {
        // Arrange
        $article = new Article();
        $article->setTitle('测试文章');
        $article->setContent('这是一篇测试文章内容');

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        // Act - 手动设置更新时间
        $manualDate = new DateTime('2020-01-01 00:00:00');
        $article->setUpdatedAt($manualDate);
        $article->setTitle('更新标题');
        $this->entityManager->flush();

        // Assert - 手动设置的时间应该被保留
        $this->assertEquals(
            '2020-01-01 00:00:00',
            $article->getUpdatedAt()->format('Y-m-d H:i:s'),
            '手动设置的更新时间应该被保留'
        );
    }

    public function test_noChanges_doesNotUpdateTimestamp(): void
    {
        // Arrange
        $article = new Article();
        $article->setTitle('测试文章');
        $article->setContent('这是一篇测试文章内容');

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $originalUpdateTime = $article->getUpdatedAt() ? clone $article->getUpdatedAt() : null;

        // 模拟时间流逝
        Carbon::setTestNow(Carbon::create(2023, 6, 15, 11, 0, 0));

        // Act - 没有对实体做任何修改，直接保存
        $this->entityManager->flush();

        // Assert - 更新时间应该保持不变
        if ($originalUpdateTime !== null && $article->getUpdatedAt() !== null) {
            $this->assertEquals(
                $originalUpdateTime->format('Y-m-d H:i:s'),
                $article->getUpdatedAt()->format('Y-m-d H:i:s'),
                '没有实际修改时，更新时间应该保持不变'
            );
        } else {
            $this->assertEquals($originalUpdateTime, $article->getUpdatedAt(), '更新时间应该保持null');
        }
    }

    public function test_presetValues_areNotOverridden(): void
    {
        // Arrange - 创建文章并预设时间
        $article = new Article();
        $article->setTitle('测试文章');
        $article->setContent('这是一篇测试文章内容');

        $presetTime = new DateTime('2020-12-25 15:30:45');
        $article->setCreatedAt(clone $presetTime);
        $article->setUpdatedAt(clone $presetTime);

        // Act
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        // Assert - 预设的时间应该被保留
        $this->assertEquals(
            '2020-12-25 15:30:45',
            $article->getCreatedAt()->format('Y-m-d H:i:s'),
            '预设的创建时间应该被保留'
        );
        $this->assertEquals(
            '2020-12-25 15:30:45',
            $article->getUpdatedAt()->format('Y-m-d H:i:s'),
            '预设的更新时间应该被保留'
        );
    }
}
