<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\ElementNode;
use Kabuto\AttributeBag;
use Kabuto\AttributeEntry;
use Kabuto\AttributeProvenance;
use Kabuto\ComponentRenderer;
use Kabuto\HtmlAttributeRenderer;
use Kabuto\HtmlSyntax;
use Kabuto\RenderContext;
use Kabuto\RenderScope;

final class HtmlNodeRenderer
{
    /**
     * Renders a normal HTML element with escaped static attribute values.
     *
     */
    public function render(
        ElementNode $node,
        RenderScope $scope,
        RenderContext $context,
        ComponentRenderer $renderer,
        NodeRenderer $nodeRenderer,
    ): string {
        $openTag = $this->openTag($node, $scope, $renderer);

        if (HtmlSyntax::isVoidElement($node->name())) {
            return $openTag;
        }

        return (
            $openTag
            . $nodeRenderer->renderNodes($node->children(), $scope, $context, $renderer)
            . '</'
            . $node->name()
            . '>'
        );
    }

    /**
     * Builds an opening tag for a normal HTML element.
     *
     */
    private function openTag(ElementNode $node, RenderScope $scope, ComponentRenderer $renderer): string
    {
        if ($node->spread() !== null) {
            $entries = [];
            foreach ($node->outputAttributes() as $attribute) {
                if ($attribute instanceof AttributeNode) {
                    $entries[] = new AttributeEntry(
                        $attribute->name(),
                        $attribute->isBare() ? true : $attribute->value(),
                        AttributeProvenance::Static,
                        $attribute->location(),
                    );
                    continue;
                }

                $location = $attribute->expression()->location();
                if ($location === null) {
                    throw \Kabuto\RenderException::at(
                        'Dynamic attribute has no source location',
                        $attribute->expression()->offset(),
                    );
                }

                $entries[] = new AttributeEntry(
                    $attribute->name(),
                    $renderer->evaluate($attribute->expression(), $scope),
                    AttributeProvenance::Dynamic,
                    $location,
                );
            }

            $expression = $node->spread()->expression();

            return (
                '<'
                . $node->name()
                . $renderer->renderSpreadAttributes(
                    $node->name(),
                    AttributeBag::fromEntries($entries),
                    $renderer->evaluate($expression, $scope),
                    $expression->location(),
                )
                . '>'
            );
        }

        $html = '<' . $node->name();

        foreach ($node->outputAttributes() as $attribute) {
            $html .= $attribute instanceof AttributeNode
                ? HtmlAttributeRenderer::renderStatic($attribute)
                : $renderer->renderDynamicAttribute(
                    $node->name(),
                    $attribute->name(),
                    $attribute->expression(),
                    $scope,
                );
        }

        return $html . '>';
    }
}
