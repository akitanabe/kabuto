<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\Node;
use Kabuto\Ast\TextNode;

final class TemplateLiteralParser
{
    /** @var list<Node> */
    private array $pendingNodes = [];

    /**
     * Stores the cursor and HTML literal reader for non-tag template content.
     */
    public function __construct(
        private SourceCursor $cursor,
        private HtmlLiteralReader $htmlLiteralReader,
        private TextInterpolationParser $interpolationParser = new TextInterpolationParser(),
    ) {}

    /**
     * Parses text or an HTML comment, or defers to tag parsing.
     */
    public function parse(): ?Node
    {
        if ($this->pendingNodes !== []) {
            return array_shift($this->pendingNodes);
        }

        if ($this->cursor->peek() !== '<') {
            return $this->parseText();
        }

        if ($this->cursor->startsWith('<!--')) {
            return new TextNode($this->htmlLiteralReader->readComment());
        }

        return null;
    }

    public function hasPendingNodes(): bool
    {
        return $this->pendingNodes !== [];
    }

    public function hasInput(): bool
    {
        return !$this->cursor->isEnd() || $this->hasPendingNodes();
    }

    public function isAtClosingTag(): bool
    {
        return !$this->hasPendingNodes() && $this->cursor->startsWith('</');
    }

    /**
     * Parses regular text while rejecting unsupported directives outside literal nodes.
     */
    /** @return list<Node> */
    public function parseTextNodes(string $text, int $startOffset): array
    {
        if (preg_match('/@(if|foreach|endif|endforeach)\b/', $text, $matches, flags: PREG_OFFSET_CAPTURE) === 1) {
            throw ParseException::at('Directives are not supported', $startOffset + $matches[0][1]);
        }

        return $this->interpolationParser->parse($text, $startOffset, $this->cursor->source());
    }

    private function parseText(): Node
    {
        $startOffset = $this->cursor->offset();
        $text = $this->cursor->readTextUntilTag();
        $nodes = $this->parseTextNodes($text, $startOffset);
        $node = array_shift($nodes);
        $this->pendingNodes = $nodes;

        return $node ?? new TextNode('');
    }
}
