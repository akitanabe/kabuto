<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\ElementNode;
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
        $openTag = $this->openTag($node->name(), $node->attributes());

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
     * @param list<\Kabuto\Ast\AttributeNode> $attributes
     */
    private function openTag(string $name, array $attributes): string
    {
        $html = '<' . $name;

        foreach ($attributes as $attribute) {
            $html .= HtmlAttributeRenderer::renderStatic($attribute);
        }

        return $html . '>';
    }
}
