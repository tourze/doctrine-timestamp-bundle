<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Enum;

enum Types: string
{
    case datetime = 'datetime';
    case timestamp = 'timestamp';
}
