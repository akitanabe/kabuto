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
     * @param callable(list<\Kabuto\Ast\Node>): string $compileNodes
     * @param callable(Expression): string $compileExpression
     */
    public function compile(ElementNode $node, callable $compileNodes, callable $compileExpression): string
    {
        $openTag = PhpSource::string('<' . $node->name());

        if ($node->spread() !== null) {
            $expression = $node->spread()->expression();
            $openTag .=
                ' . $renderer->renderSpreadAttributes('
                . PhpSource::string($node->name())
                . ', '
                . new AttributeBagCompiler()->compile($node->outputAttributes(), $compileExpression)
                . ', $renderer->evaluate('
                . $compileExpression($expression)
                . ', $scope), '
                . PhpSource::location($expression->location())
                . ')';

            return $this->finish($node, $openTag, $compileNodes);
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
                . ', $scope)';
        }

        return $this->finish($node, $openTag, $compileNodes);
    }

    /** @param callable(list<\Kabuto\Ast\Node>): string $compileNodes */
    private function finish(ElementNode $node, string $openTag, callable $compileNodes): string
    {
        return (
            $openTag
            . ' . '
            . PhpSource::string('>')
            . ' . '
            . $compileNodes($node->children())
            . (HtmlSyntax::isVoidElement($node->name()) ? '' : ' . ' . PhpSource::string('</' . $node->name() . '>'))
        );
    }
}
