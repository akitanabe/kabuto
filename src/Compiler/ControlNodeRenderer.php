<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\ForeachNode;
use Kabuto\Ast\IfNode;
use Kabuto\ComponentRenderer;
use Kabuto\ControlFlow;
use Kabuto\RenderContext;
use Kabuto\RenderScope;

final class ControlNodeRenderer
{
    public function render(
        IfNode|ForeachNode $node,
        RenderScope $scope,
        RenderContext $context,
        ComponentRenderer $renderer,
        NodeRenderer $nodeRenderer,
    ): string {
        return $node instanceof IfNode
            ? $this->renderIf($node, $scope, $context, $renderer, $nodeRenderer)
            : $this->renderForeach($node, $scope, $context, $renderer, $nodeRenderer);
    }

    private function renderIf(
        IfNode $node,
        RenderScope $scope,
        RenderContext $context,
        ComponentRenderer $renderer,
        NodeRenderer $nodeRenderer,
    ): string {
        foreach ($node->branches() as $branch) {
            if (!ControlFlow::condition($renderer->evaluate($branch->condition(), $scope))) {
                continue;
            }

            return $nodeRenderer->renderNodes($branch->children(), $scope, $context, $renderer);
        }

        return $nodeRenderer->renderNodes($node->elseChildren() ?? [], $scope, $context, $renderer);
    }

    private function renderForeach(
        ForeachNode $node,
        RenderScope $scope,
        RenderContext $context,
        ComponentRenderer $renderer,
        NodeRenderer $nodeRenderer,
    ): string {
        $collection = ControlFlow::iterable(
            $renderer->evaluate($node->collection(), $scope),
            $node->collection()->location(),
            $node->collection()->offset(),
        );
        $html = '';

        foreach ($collection as $value) {
            $html .= $nodeRenderer->renderNodes(
                $node->children(),
                $scope->with($node->item(), $value),
                $context,
                $renderer,
            );
        }

        return $html;
    }
}
