<?php

namespace Tourze\DoctrineTimestampBundle\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;

/**
 * 自动记录的创建和更新时间
 */
trait TimestampableAware
{
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeImmutable $createTime = null;

    #[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeImmutable $updateTime = null;

    public function setCreateTime(?\DateTimeImmutable $createdAt): static
    {
        $this->createTime = $createdAt;
        
        return $this;
    }

    public function getCreateTime(): ?\DateTimeImmutable
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeImmutable $updateTime): static
    {
        $this->updateTime = $updateTime;
        
        return $this;
    }

    public function getUpdateTime(): ?\DateTimeImmutable
    {
        return $this->updateTime;
    }

    public function retrieveTimestampArray(): array
    {
        return [
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }
}
