<?php

declare(strict_types=1);

namespace Tourze\DoctrineTimestampBundle\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
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

        $timestampInfo = $this->analyzeTimestampProperties($classReflection);

        return $this->generateRuleErrors($classReflection, $timestampInfo);
    }

    /**
     * 分析时间戳属性的存在情况和注解状态
     *
     * @return array{hasCreateTime: bool, hasUpdateTime: bool, hasCreateTimeAnnotation: bool, hasUpdateTimeAnnotation: bool}
     */
    private function analyzeTimestampProperties(ClassReflection $classReflection): array
    {
        $hasCreateTime = false;
        $hasUpdateTime = false;
        $hasCreateTimeAnnotation = false;
        $hasUpdateTimeAnnotation = false;

        $nativeReflection = $classReflection->getNativeReflection();
        $properties = $nativeReflection->getProperties();

        foreach ($properties as $property) {
            if ($this->isCreateTimeProperty($property)) {
                $hasCreateTime = true;
                $hasCreateTimeAnnotation = $this->hasCreateTimeColumnAttribute($property);
            }

            if ($this->isUpdateTimeProperty($property)) {
                $hasUpdateTime = true;
                $hasUpdateTimeAnnotation = $this->hasUpdateTimeColumnAttribute($property);
            }
        }

        return [
            'hasCreateTime' => $hasCreateTime,
            'hasUpdateTime' => $hasUpdateTime,
            'hasCreateTimeAnnotation' => $hasCreateTimeAnnotation,
            'hasUpdateTimeAnnotation' => $hasUpdateTimeAnnotation,
        ];
    }

    /**
     * 检查是否为 createTime 属性
     */
    private function isCreateTimeProperty(\ReflectionProperty $property): bool
    {
        return in_array($property->getName(), ['createTime'], true);
    }

    /**
     * 检查是否为 updateTime 属性
     */
    private function isUpdateTimeProperty(\ReflectionProperty $property): bool
    {
        return in_array($property->getName(), ['updateTime'], true);
    }

    /**
     * 检查属性是否有 CreateTimeColumn 注解
     */
    private function hasCreateTimeColumnAttribute(\ReflectionProperty $property): bool
    {
        $attributes = $property->getAttributes(CreateTimeColumn::class);
        return count($attributes) > 0;
    }

    /**
     * 检查属性是否有 UpdateTimeColumn 注解
     */
    private function hasUpdateTimeColumnAttribute(\ReflectionProperty $property): bool
    {
        $attributes = $property->getAttributes(UpdateTimeColumn::class);
        return count($attributes) > 0;
    }

    /**
     * 根据时间戳信息生成规则错误
     *
     * @param array{hasCreateTime: bool, hasUpdateTime: bool, hasCreateTimeAnnotation: bool, hasUpdateTimeAnnotation: bool} $timestampInfo
     * @return array<RuleError>
     */
    private function generateRuleErrors(ClassReflection $classReflection, array $timestampInfo): array
    {
        // 如果不是同时存在 createTime 和 updateTime 字段，则不需要建议使用 trait
        if (!($timestampInfo['hasCreateTime'] && $timestampInfo['hasUpdateTime'])) {
            return [];
        }

        // 情况1：都有注解的情况（原有逻辑）
        if ($timestampInfo['hasCreateTimeAnnotation'] && $timestampInfo['hasUpdateTimeAnnotation']) {
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
        if (!$timestampInfo['hasCreateTimeAnnotation'] && !$timestampInfo['hasUpdateTimeAnnotation']) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        '实体类 %s 同时定义了 createTime 和 updateTime 字段，建议使用 \Tourze\DoctrineTimestampBundle\Traits\TimestampableAware trait 来自动管理时间戳。',
                        $classReflection->getName()
                    )
                )->build(),
            ];
        }

        return [];
    }
}
