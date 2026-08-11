<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\ComponentNode;
use Kabuto\Ast\ElementNode;
use Kabuto\Ast\InterpolationNode;
use Kabuto\Ast\Node;
use Kabuto\Ast\SlotOutletNode;
use Kabuto\Ast\TextNode;
use Kabuto\ComponentRenderer;
use Kabuto\RenderContext;
use Kabuto\RenderScope;

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
     */
    public function renderNodes(
        array $nodes,
        RenderScope $scope,
        RenderContext $context,
        ComponentRenderer $renderer,
    ): string {
        $html = '';

        foreach ($nodes as $node) {
            $html .= $this->renderNode($node, $scope, $context, $renderer);
        }

        return $html;
    }

    /**
     * Renders one supported AST node.
     *
     */
    private function renderNode(
        Node $node,
        RenderScope $scope,
        RenderContext $context,
        ComponentRenderer $renderer,
    ): string {
        if ($node instanceof TextNode) {
            return $node->content();
        }

        if ($node instanceof InterpolationNode) {
            return $renderer->renderText($node->expression(), $scope);
        }

        if ($node instanceof ElementNode) {
            return $this->htmlRenderer->render($node, $scope, $context, $renderer, $this);
        }

        if ($node instanceof ComponentNode) {
            return $this->componentRenderer->render($node, $scope, $context, $renderer, $this);
        }

        if ($node instanceof SlotOutletNode) {
            return $renderer->slotOutlet($node->name(), $context);
        }

        throw CompileException::unsupportedNode($node);
    }
}
