<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\TextNode;

final readonly class TemplateLiteralParser
{
    /**
     * Stores the cursor and HTML literal reader for non-tag template content.
     */
    public function __construct(
        private SourceCursor $cursor,
        private HtmlLiteralReader $htmlLiteralReader,
    ) {}

    /**
     * Parses text or an HTML comment, or defers to tag parsing.
     */
    public function parse(): ?TextNode
    {
        if ($this->cursor->peek() !== '<') {
            return $this->parseText();
        }

        if ($this->cursor->startsWith('<!--')) {
            return new TextNode($this->htmlLiteralReader->readComment());
        }

        return null;
    }

    /**
     * Parses regular text while rejecting unsupported directives outside literal nodes.
     */
    private function parseText(): TextNode
    {
        $startOffset = $this->cursor->offset();
        $text = $this->cursor->readTextUntilTag();

        if (preg_match('/@(if|foreach|endif|endforeach)\b/', $text, $matches, flags: PREG_OFFSET_CAPTURE) === 1) {
            throw ParseException::at('Directives are not supported', $startOffset + $matches[0][1]);
        }

        return new TextNode($text);
    }
}
