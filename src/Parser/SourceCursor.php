<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use SensitiveParameter;

final class SourceCursor
{
    private int $offset = 0;

    private int $length;

    /**
     * Stores the source string and initial cursor position.
     */
    public function __construct(
        private readonly string $source,
    ) {
        $this->length = strlen($source);
    }

    /**
     * Returns the current byte offset.
     */
    public function offset(): int
    {
        return $this->offset;
    }

    /**
     * Returns whether the full source has been consumed.
     */
    public function isEnd(): bool
    {
        return $this->offset >= $this->length;
    }

    /**
     * Returns the current one-byte character.
     */
    public function peek(): string
    {
        return $this->source[$this->offset] ?? '';
    }

    /**
     * Returns whether the remaining source starts with the given token.
     */
    public function startsWith(#[SensitiveParameter] string $token): bool
    {
        return str_starts_with(substr($this->source, offset: $this->offset), $token);
    }

    /**
     * Advances past an expected token.
     */
    public function expect(#[SensitiveParameter] string $token): void
    {
        if (!$this->startsWith($token)) {
            throw ParseException::at('Expected ' . $token, $this->offset);
        }

        $this->offset += strlen($token);
    }

    /**
     * Advances past ASCII whitespace.
     */
    public function skipWhitespace(): void
    {
        while (!$this->isEnd() && ctype_space($this->peek())) {
            $this->offset++;
        }
    }

    /**
     * Reads an HTML-compatible tag or attribute name.
     */
    public function readName(): string
    {
        if (preg_match('/\G[A-Za-z][A-Za-z0-9:_-]*/', $this->source, $matches, flags: 0, offset: $this->offset) !== 1) {
            throw ParseException::at('Expected name', $this->offset);
        }

        $this->offset += strlen($matches[0]);

        return $matches[0];
    }

    /**
     * Reads a single-quoted or double-quoted attribute value.
     */
    public function readQuotedValue(): string
    {
        $quote = $this->peek();
        if ($quote !== '"' && $quote !== "'") {
            throw ParseException::at('Expected quoted attribute value', $this->offset);
        }

        $this->offset++;
        $start = $this->offset;

        while (!$this->isEnd() && $this->peek() !== $quote) {
            $this->offset++;
        }

        if ($this->isEnd()) {
            throw ParseException::at('Unterminated attribute value', $start);
        }

        $value = substr($this->source, $start, $this->offset - $start);
        $this->offset++;

        return $value;
    }

    /**
     * Reads literal text up to the next tag boundary.
     */
    public function readTextUntilTag(): string
    {
        $start = $this->offset;

        while (!$this->isEnd() && $this->peek() !== '<') {
            $this->offset++;
        }

        return substr($this->source, $start, $this->offset - $start);
    }
}
