<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\ElementNode;
use Kabuto\Expression;
use Kabuto\HtmlAttributeRenderer;
use Kabuto\HtmlSyntax;

final class ElementNodeCompiler
{
    /**
     * @param callable(Expression): string $compileExpression
     */
    public function compileOpenTag(ElementNode $node, callable $compileExpression, string $scope): string
    {
        $openTag = PhpSource::string('<' . $node->name());

        if ($node->spread() !== null) {
            $expression = $node->spread()->expression();
            $openTag .=
                ' . $renderer->renderSpreadAttributes('
                . PhpSource::string($node->name())
                . ', '
                . new AttributeBagCompiler()->compile($node->outputAttributes(), $compileExpression, $scope)
                . ', $renderer->evaluate('
                . $compileExpression($expression)
                . ', '
                . $scope
                . '), '
                . PhpSource::location($expression->location())
                . ')';

            return $openTag . ' . ' . PhpSource::string('>');
        }

        foreach ($node->outputAttributes() as $attribute) {
            if ($attribute instanceof AttributeNode) {
                $openTag .= ' . ' . PhpSource::string(HtmlAttributeRenderer::renderStatic($attribute));
                continue;
            }

            $openTag .=
                ' . $renderer->renderDynamicAttribute('
                . PhpSource::string($node->name())
                . ', '
                . PhpSource::string($attribute->name())
                . ', '
                . $compileExpression($attribute->expression())
                . ', '
                . $scope
                . ')';
        }

        return $openTag . ' . ' . PhpSource::string('>');
    }

    public function closingTag(ElementNode $node): ?string
    {
        return HtmlSyntax::isVoidElement($node->name()) ? null : '</' . $node->name() . '>';
    }
}
