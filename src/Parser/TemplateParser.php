<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\Node;
use Kabuto\Ast\TextNode;

final class TemplateParser
{
    private TagParser $tagParser;

    private BodyNodeParser $bodyNodeParser;

    /**
     * Stores parser collaborators for a single source cursor.
     */
    public function __construct(
        private readonly SourceCursor $cursor,
    ) {
        $this->tagParser = new TagParser($cursor);
        $this->bodyNodeParser = new BodyNodeParser($cursor, $this);
    }

    /**
     * Parses all top-level nodes.
     *
     * @return list<Node>
     */
    public function parse(): array
    {
        $nodes = [];

        while (!$this->cursor->isEnd()) {
            if ($this->cursor->startsWith('</')) {
                throw ParseException::at('Unexpected closing tag', $this->cursor->offset());
            }

            $nodes[] = $this->parseTopLevelNode();
        }

        return $nodes;
    }

    /**
     * Parses children until the requested closing tag is reached.
     *
     * @return list<Node>
     */
    public function parseChildren(string $closingTag): array
    {
        $nodes = [];

        while (!$this->cursor->isEnd()) {
            if ($this->cursor->startsWith('</')) {
                $this->parseClosingTag($closingTag);

                return $nodes;
            }

            $nodes[] = $this->parseTopLevelNode();
        }

        throw ParseException::at('Missing closing tag ' . $closingTag, $this->cursor->offset());
    }

    /**
     * Parses component children where named slots are accepted.
     *
     * @return list<Node>
     */
    public function parseComponentChildren(string $closingTag): array
    {
        $nodes = [];

        while (!$this->cursor->isEnd()) {
            if ($this->cursor->startsWith('</')) {
                $this->parseClosingTag($closingTag);

                return $nodes;
            }

            $nodes[] = $this->parseComponentChild();
        }

        throw ParseException::at('Missing closing tag ' . $closingTag, $this->cursor->offset());
    }

    /**
     * Parses one node where named slots are not accepted.
     */
    private function parseTopLevelNode(): Node
    {
        if ($this->cursor->peek() !== '<') {
            return new TextNode($this->cursor->readTextUntilTag());
        }

        $tag = $this->tagParser->readOpenTag();
        return $this->bodyNodeParser->parseTopLevelTag($tag);
    }

    /**
     * Parses one component child where x-slot is a named slot.
     */
    private function parseComponentChild(): Node
    {
        if ($this->cursor->peek() !== '<') {
            return new TextNode($this->cursor->readTextUntilTag());
        }

        $tag = $this->tagParser->readOpenTag();
        return $this->bodyNodeParser->parseComponentTag($tag);
    }

    /**
     * Parses the closing tag matching the current element or component.
     */
    private function parseClosingTag(string $expected): void
    {
        $this->cursor->expect('</');
        $actual = $this->cursor->readName();
        $this->cursor->skipWhitespace();
        $this->cursor->expect('>');

        if ($actual !== $expected) {
            throw ParseException::at('Expected closing tag ' . $expected . ', got ' . $actual, $this->cursor->offset());
        }
    }
}
