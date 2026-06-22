<?php

declare(strict_types=1);

namespace Kabuto\Ast;

final readonly class TextNode implements Node
{
    /**
     * Stores literal text content from the template.
     */
    public function __construct(
        private string $content,
    ) {}

    /**
     * Identifies this AST node as literal text.
     */
    public function kind(): string
    {
        return 'text';
    }

    /**
     * Returns the literal text content.
     */
    public function content(): string
    {
        return $this->content;
    }
}
