<?php

declare(strict_types=1);

namespace Kabuto\Parser;

final readonly class HtmlLiteralReader
{
    /**
     * Stores the cursor from which HTML literal syntax is consumed.
     */
    public function __construct(
        private SourceCursor $cursor,
    ) {}

    /**
     * Reads an HTML comment without interpreting its contents.
     */
    public function readComment(): string
    {
        $start = $this->cursor->offset();
        $comment = '<!--';
        $this->cursor->expect($comment);

        while (!$this->cursor->isEnd()) {
            if ($this->cursor->startsWith('-->')) {
                $this->cursor->expect('-->');

                return $comment . '-->';
            }

            $comment .= $this->readCharacter();
        }

        throw ParseException::at('Unterminated HTML comment', $start);
    }

    /**
     * Reads a standard HTML doctype while preserving its source form.
     */
    public function readDoctype(): string
    {
        $start = $this->cursor->offset();
        $declaration = $this->readDeclaration();

        if (preg_match('/^<!DOCTYPE[\x20\t\r\n]+html>$/i', $declaration) !== 1) {
            throw ParseException::at('Expected standard HTML doctype', $start);
        }

        return $declaration;
    }

    /**
     * Reads raw text until its matching closing tag, consuming that closing tag.
     */
    public function readRawTextUntilClosingTag(string $expected): string
    {
        $start = $this->cursor->offset();
        $content = '';

        while (!$this->cursor->isEnd()) {
            $content .= $this->readCharacter();

            if (preg_match('/<\/([A-Za-z][A-Za-z0-9:_-]*)\s*>$/i', $content, $matches) !== 1) {
                continue;
            }

            if (strcasecmp($matches[1], $expected) !== 0) {
                continue;
            }

            return substr(string: $content, offset: 0, length: -strlen($matches[0]));
        }

        throw ParseException::at('Missing closing tag ' . $expected, $start + strlen($content));
    }

    private function readCharacter(): string
    {
        $character = $this->cursor->peek();
        $this->cursor->expect($character);

        return $character;
    }

    private function readDeclaration(): string
    {
        $start = $this->cursor->offset();
        $declaration = '';

        while (!$this->cursor->isEnd()) {
            $character = $this->readCharacter();
            $declaration .= $character;

            if ($character === '>') {
                return $declaration;
            }
        }

        throw ParseException::at('Unterminated doctype declaration', $start);
    }
}
