<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\PropNode;

final readonly class TagParser
{
    /**
     * Stores the source cursor used for tag parsing.
     */
    public function __construct(
        private SourceCursor $cursor,
    ) {}

    /**
     * Parses an opening tag and its attributes.
     */
    public function readOpenTag(): OpenTag
    {
        $this->cursor->expect('<');
        $name = $this->cursor->readName();
        [$attributes, $props, $selfClosing] = $this->readAttributes();

        return new OpenTag($name, $attributes, $props, $selfClosing);
    }

    /**
     * Parses static attributes and dynamic props from the current tag.
     *
     * @return array{0: list<AttributeNode>, 1: list<PropNode>, 2: bool}
     */
    private function readAttributes(): array
    {
        $attributes = [];
        $props = [];

        while (true) {
            $this->cursor->skipWhitespace();

            if ($this->cursor->startsWith('/>')) {
                $this->cursor->expect('/>');

                return [$attributes, $props, true];
            }

            if ($this->cursor->startsWith('>')) {
                $this->cursor->expect('>');

                return [$attributes, $props, false];
            }

            [$name, $value, $isDynamic] = $this->readAttribute();

            if ($isDynamic) {
                $props[] = new PropNode($name, $this->validateDynamicExpression($value));
                continue;
            }

            $attributes[] = new AttributeNode($name, $value);
        }
    }

    /**
     * Parses one quoted attribute assignment.
     *
     * @return array{0: string, 1: string, 2: bool}
     */
    private function readAttribute(): array
    {
        $isDynamic = false;
        if ($this->cursor->startsWith(':')) {
            $isDynamic = true;
            $this->cursor->expect(':');
        }

        $name = $this->cursor->readName();
        $this->cursor->skipWhitespace();
        $this->cursor->expect('=');
        $this->cursor->skipWhitespace();

        return [$name, $this->cursor->readQuotedValue(), $isDynamic];
    }

    /**
     * Validates the supported dynamic prop expression form.
     */
    private function validateDynamicExpression(string $expression): string
    {
        if (preg_match('/^\$[A-Za-z_][A-Za-z0-9_]*$/', $expression) !== 1) {
            throw ParseException::at('Dynamic props only support simple variable expressions', $this->cursor->offset());
        }

        return $expression;
    }
}
