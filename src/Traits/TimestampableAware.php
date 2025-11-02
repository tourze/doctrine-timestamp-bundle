<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;

/**
 * 自动记录的创建和更新时间
 */
trait TimestampableAware
{
    #[CreateTimeColumn]
    #[ORM\Column(name: 'create_time', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeImmutable $createTime = null;

    #[UpdateTimeColumn]
    #[ORM\Column(name: 'update_time', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    #[Assert\Type(type: \DateTimeInterface::class)]
    private ?\DateTimeImmutable $updateTime = null;

    final public function setCreateTime(?\DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }

    final public function getCreateTime(): ?\DateTimeImmutable
    {
        return $this->createTime;
    }

    final public function setUpdateTime(?\DateTimeImmutable $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    final public function getUpdateTime(): ?\DateTimeImmutable
    {
        return $this->updateTime;
    }

    /**
     * @return array<string, string|null>
     */
    public function retrieveTimestampArray(): array
    {
        return [
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }
}
