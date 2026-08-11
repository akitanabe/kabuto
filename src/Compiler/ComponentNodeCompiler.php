<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\ComponentNode;
use Kabuto\Ast\DynamicAttributeNode;
use Kabuto\Ast\PropNode;
use Kabuto\Expression;

final class ComponentNodeCompiler
{
    /** @param callable(Expression): string $compileExpression */
    public function compile(ComponentNode $node, callable $compileExpression, string $slot, string $slots): string
    {
        $inputs = [...$node->props(), ...$node->callerAttributes()];
        usort(
            $inputs,
            static fn(
                PropNode|AttributeNode|DynamicAttributeNode $left,
                PropNode|AttributeNode|DynamicAttributeNode $right,
            ): int => $left->position() <=> $right->position(),
        );
        $statements = [];

        foreach ($inputs as $input) {
            $statements[] = match (true) {
                $input instanceof PropNode => $this->compileProp($input, $compileExpression),
                $input instanceof AttributeNode => $this->compileStaticAttribute($input),
                default => $this->compileDynamicAttribute($input, $compileExpression),
            };
        }

        return (
            '(static function () use ($scope, $renderer, $context): string {'
            . ' $props = []; $attributeEntries = []; '
            . implode(' ', $statements)
            . ' return $renderer->component('
            . PhpSource::string($node->name())
            . ', new \\Kabuto\\ComponentInvocation('
            . '$props, \\Kabuto\\AttributeBag::fromEntries($attributeEntries), '
            . $slot
            . ', '
            . $slots
            . ', $context)); })()'
        );
    }

    /** @param callable(Expression): string $compileExpression */
    private function compileProp(PropNode $prop, callable $compileExpression): string
    {
        return (
            '$props['
            . PhpSource::string($prop->name())
            . '] = $renderer->evaluate('
            . $compileExpression($prop->expressionData())
            . ', $scope);'
        );
    }

    private function compileStaticAttribute(AttributeNode $attribute): string
    {
        return (
            '$attributeEntries[] = new \\Kabuto\\AttributeEntry('
            . PhpSource::string($attribute->name())
            . ', '
            . ($attribute->isBare() ? 'true' : PhpSource::string($attribute->value()))
            . ', \\Kabuto\\AttributeProvenance::Static, '
            . PhpSource::location($attribute->location())
            . ');'
        );
    }

    /** @param callable(Expression): string $compileExpression */
    private function compileDynamicAttribute(DynamicAttributeNode $attribute, callable $compileExpression): string
    {
        return (
            '$attributeEntries[] = new \\Kabuto\\AttributeEntry('
            . PhpSource::string($attribute->name())
            . ', $renderer->evaluate('
            . $compileExpression($attribute->expression())
            . ', $scope), \\Kabuto\\AttributeProvenance::Dynamic, '
            . PhpSource::location($attribute->expression()->location())
            . ');'
        );
    }
}
