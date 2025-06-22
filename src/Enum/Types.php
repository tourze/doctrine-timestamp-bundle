<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum Types: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case datetime = 'datetime';
    case timestamp = 'timestamp';

    public function getLabel(): string
    {
        return match ($this) {
            self::datetime => '日期时间',
            self::timestamp => '时间戳',
        };
    }
}
