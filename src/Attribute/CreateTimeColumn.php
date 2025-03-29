<?php

namespace Tourze\DoctrineTimestampBundle\Attribute;

use Tourze\DoctrineTimestampBundle\Enum\Types;

/**
 * 记录创建时间
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class CreateTimeColumn
{
    public function __construct(
        public Types $type = Types::datetime
    ) {
    }
}
