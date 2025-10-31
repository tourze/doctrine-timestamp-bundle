<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\PHPUnitDoctrineEntity\EntityChecker;

/**
 * 检查实体是否应该使用 TimestampableAware trait
 *
 * @implements Rule<InClassNode>
 */
class UseTimestampableAwareTraitRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     *
     * @return array<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        // 只检查实体类（假设实体类在 Entity 命名空间下或者使用了 @ORM\Entity 注解）
        if (!EntityChecker::isEntityClass($classReflection->getNativeReflection())) {
            return [];
        }

        // 检查是否已经使用了 TimestampableAware trait
        if ($classReflection->hasTraitUse(TimestampableAware::class)) {
            return [];
        }

        // 查找 createTime 和 updateTime 属性
        $hasCreateTime = false;
        $hasUpdateTime = false;
        $hasCreateTimeAnnotation = false;
        $hasUpdateTimeAnnotation = false;

        $nativeReflection = $classReflection->getNativeReflection();
        $properties = $nativeReflection->getProperties();

        foreach ($properties as $property) {
            $propertyName = $property->getName();

            // 检查属性名
            if (in_array($propertyName, ['createTime'], true)) {
                $hasCreateTime = true;

                // 检查是否有 CreateTimeColumn 注解
                $attributes = $property->getAttributes(CreateTimeColumn::class);
                if (count($attributes) > 0) {
                    $hasCreateTimeAnnotation = true;
                }
            }

            if (in_array($propertyName, ['updateTime'], true)) {
                $hasUpdateTime = true;

                // 检查是否有 UpdateTimeColumn 注解
                $attributes = $property->getAttributes(UpdateTimeColumn::class);
                if (count($attributes) > 0) {
                    $hasUpdateTimeAnnotation = true;
                }
            }
        }

        // 如果同时存在 createTime 和 updateTime 字段
        if ($hasCreateTime && $hasUpdateTime) {
            // 情况1：都有注解的情况（原有逻辑）
            if ($hasCreateTimeAnnotation && $hasUpdateTimeAnnotation) {
                return [
                    RuleErrorBuilder::message(
                        sprintf(
                            '实体类 %s 同时定义了 createTime 和 updateTime 字段并使用了相应注解，请改用 \Tourze\DoctrineTimestampBundle\Traits\TimestampableAware trait 来简化代码。',
                            $classReflection->getName()
                        )
                    )->build(),
                ];
            }

            // 情况2：都没有注解的情况（新增逻辑）
            if (!$hasCreateTimeAnnotation && !$hasUpdateTimeAnnotation) {
                return [
                    RuleErrorBuilder::message(
                        sprintf(
                            '实体类 %s 同时定义了 createTime 和 updateTime 字段，建议使用 \Tourze\DoctrineTimestampBundle\Traits\TimestampableAware trait 来自动管理时间戳。',
                            $classReflection->getName()
                        )
                    )->build(),
                ];
            }
        }

        return [];
    }
}
