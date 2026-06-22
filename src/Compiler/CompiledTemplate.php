<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\Node;
use Kabuto\ComponentRenderer;
use Kabuto\RenderContext;

final class CompiledTemplate
{
    /**
     * Stores compiled AST nodes and the renderer used to evaluate them.
     *
     * @param list<Node> $nodes
     */
    public function __construct(
        private array $nodes,
        private NodeRenderer $nodeRenderer = new NodeRenderer(),
    ) {}

    /**
     * Renders the compiled template with data, context, and component renderer.
     *
     * @param array<string, mixed> $data
     */
    public function render(array $data, RenderContext $context, ComponentRenderer $renderer): string
    {
        return $this->nodeRenderer->renderNodes($this->nodes, $data, $context, $renderer);
    }

    /**
     * Allows the compiled template to be used as a callable renderer.
     *
     * @param array<string, mixed> $data
     */
    public function __invoke(array $data, RenderContext $context, ComponentRenderer $renderer): string
    {
        return $this->render($data, $context, $renderer);
    }
}
