<?php

namespace Tourze\DoctrineTimestampBundle\Tests\Fixtures\Entity;

class NoAttributesEntity
{
    private string $name;

    private int $value;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;
        return $this;
    }
}
