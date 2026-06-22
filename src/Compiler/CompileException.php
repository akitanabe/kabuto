<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use RuntimeException;

final class CompileException extends RuntimeException
{
    /**
     * Creates an exception for AST nodes that cannot be compiled.
     */
    public static function unsupportedNode(object $node): self
    {
        return new self('Unsupported AST node: ' . $node::class);
    }
}
