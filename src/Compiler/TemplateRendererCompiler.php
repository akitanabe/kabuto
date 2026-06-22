<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Ast\Node;

final class TemplateRendererCompiler
{
    /**
     * Compiles AST nodes into a reusable renderer without evaluating PHP source.
     *
     * @param list<Node> $nodes
     */
    public function compile(array $nodes): CompiledTemplate
    {
        return new CompiledTemplate($nodes);
    }
}
