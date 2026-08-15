<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\DynamicAttributeNode;
use Kabuto\Expression;

final class AttributeBagCompiler
{
    /**
     * @param list<AttributeNode|DynamicAttributeNode> $attributes
     * @param callable(Expression): string $compileExpression
     */
    public function compile(array $attributes, callable $compileExpression, string $scope): string
    {
        $entries = [];

        foreach ($attributes as $attribute) {
            if ($attribute instanceof AttributeNode) {
                $entries[] =
                    'new \\Kabuto\\AttributeEntry('
                    . PhpSource::string($attribute->name())
                    . ', '
                    . ($attribute->isBare() ? 'true' : PhpSource::string($attribute->value()))
                    . ', \\Kabuto\\AttributeProvenance::Static, '
                    . PhpSource::location($attribute->location())
                    . ')';
                continue;
            }

            $entries[] =
                'new \\Kabuto\\AttributeEntry('
                . PhpSource::string($attribute->name())
                . ', $renderer->evaluate('
                . $compileExpression($attribute->expression())
                . ', '
                . $scope
                . '), \\Kabuto\\AttributeProvenance::Dynamic, '
                . PhpSource::location($attribute->expression()->location())
                . ')';
        }

        return '\\Kabuto\\AttributeBag::fromEntries([' . implode(', ', $entries) . '])';
    }
}
