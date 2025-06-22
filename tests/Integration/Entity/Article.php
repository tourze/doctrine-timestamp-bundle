<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Integration\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Enum\Types as TimestampTypes;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity]
#[ORM\Table(name: 'article', options: ['comment' => '文章表'])]
class Article implements \Stringable
{
    use TimestampableAware;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ['comment' => '标题'])]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '内容'])]
    private ?string $content = null;

    #[ORM\Column(nullable: true, options: ['comment' => '创建时间'])]
    #[CreateTimeColumn(type: TimestampTypes::datetime)]
    private ?DateTime $createdAt = null;

    #[ORM\Column(nullable: true, options: ['comment' => '更新时间'])]
    #[UpdateTimeColumn(type: TimestampTypes::datetime)]
    private ?DateTime $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
