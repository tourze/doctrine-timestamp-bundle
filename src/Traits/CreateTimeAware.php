<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;

/**
 * 为需要自动创建时间戳跟踪的实体提供的 Trait。
 *
 * 此 trait 在项目中被多个包广泛使用，
 * 提供标准化的创建时间功能。
 *
 * @phpstan-ignore-next-line trait.unused
 */
trait CreateTimeAware
{
    #[CreateTimeColumn]
    #[ORM\Column(name: 'create_time', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeImmutable $createTime = null;

    public function getCreateTime(): ?\DateTimeImmutable
    {
        return $this->createTime;
    }

    public function setCreateTime(?\DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }
}
