<?php

namespace Tourze\DoctrineTimestampBundle\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;

trait CreateTimeAware
{
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeImmutable $createTime = null;

    public function getCreateTime(): ?\DateTimeImmutable
    {
        return $this->createTime;
    }

    public function setCreateTime(?\DateTimeImmutable $createdAt): self
    {
        $this->createTime = $createdAt;

        return $this;
    }
}
