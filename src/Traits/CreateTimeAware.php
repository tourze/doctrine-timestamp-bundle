<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;


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
