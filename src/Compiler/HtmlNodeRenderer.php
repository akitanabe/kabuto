<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\ElementNode;
use Kabuto\ComponentRenderer;
use Kabuto\RenderContext;

final class HtmlNodeRenderer
{
    /**
     * Renders a normal HTML element with escaped static attribute values.
     *
     * @param array<string, mixed> $data
     */
    public function render(
        ElementNode $node,
        array $data,
        RenderContext $context,
        ComponentRenderer $renderer,
        NodeRenderer $nodeRenderer,
    ): string {
        return (
            $this->openTag($node->name(), $node->attributes())
            . $nodeRenderer->renderNodes($node->children(), $data, $context, $renderer)
            . '</'
            . $node->name()
            . '>'
        );
    }

    /**
     * Builds an opening tag for a normal HTML element.
     *
     * @param list<AttributeNode> $attributes
     */
    private function openTag(string $name, array $attributes): string
    {
        $html = '<' . $name;

        foreach ($attributes as $attribute) {
            $html .=
                ' '
                . $attribute->name()
                . '="'
                . htmlspecialchars($attribute->value(), ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8')
                . '"';
        }

        return $html . '>';
    }
}
