<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Fixtures\Entity;

use DateTime;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

class PresetValuesEntity
{
    use TimestampableAware;
    #[CreateTimeColumn]
    private DateTime $createdAt;

    #[UpdateTimeColumn]
    private DateTime $updatedAt;

    public function __construct()
    {
        // 预设值
        $this->createdAt = new DateTime('2023-01-01 00:00:00');
        $this->updatedAt = new DateTime('2023-01-01 00:00:00');
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
