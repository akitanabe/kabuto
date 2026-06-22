<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\ComponentNode;
use Kabuto\Ast\ElementNode;
use Kabuto\Ast\Node;
use Kabuto\Ast\TextNode;
use Kabuto\ComponentRenderer;
use Kabuto\RenderContext;

final class NodeRenderer
{
    /**
     * Stores specialized renderers for HTML nodes and component invocations.
     */
    public function __construct(
        private HtmlNodeRenderer $htmlRenderer = new HtmlNodeRenderer(),
        private ComponentNodeRenderer $componentRenderer = new ComponentNodeRenderer(),
    ) {}

    /**
     * Renders a list of AST nodes.
     *
     * @param list<Node> $nodes
     * @param array<string, mixed> $data
     */
    public function renderNodes(array $nodes, array $data, RenderContext $context, ComponentRenderer $renderer): string
    {
        $html = '';

        foreach ($nodes as $node) {
            $html .= $this->renderNode($node, $data, $context, $renderer);
        }

        return $html;
    }

    /**
     * Renders one supported AST node.
     *
     * @param array<string, mixed> $data
     */
    private function renderNode(Node $node, array $data, RenderContext $context, ComponentRenderer $renderer): string
    {
        if ($node instanceof TextNode) {
            return $node->content();
        }

        if ($node instanceof ElementNode) {
            return $this->htmlRenderer->render($node, $data, $context, $renderer, $this);
        }

        if ($node instanceof ComponentNode) {
            return $this->componentRenderer->render($node, $data, $context, $renderer, $this);
        }

        throw CompileException::unsupportedNode($node);
    }
}
