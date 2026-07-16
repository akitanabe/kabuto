<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Closure;
use Kabuto\Ast\Node;
use Kabuto\Ast\TextNode;

final class TemplateParser
{
    private TagParser $tagParser;

    private BodyNodeParser $bodyNodeParser;

    private HtmlLiteralReader $htmlLiteralReader;

    private TemplateLiteralParser $templateLiteralParser;

    /**
     * Stores parser collaborators for a single source cursor.
     */
    public function __construct(
        private readonly SourceCursor $cursor,
        ComponentPrefix $componentPrefix,
    ) {
        $this->htmlLiteralReader = new HtmlLiteralReader($cursor);
        $this->tagParser = new TagParser($cursor);
        $this->bodyNodeParser = new BodyNodeParser($cursor, $this, $componentPrefix, $this->htmlLiteralReader);
        $this->templateLiteralParser = new TemplateLiteralParser($cursor, $this->htmlLiteralReader);
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

            $nodes[] = $this->parseNodeWithoutDoctype($this->bodyNodeParser->parseTopLevelTag(...));
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

            $nodes[] = $this->parseNodeWithoutDoctype($this->bodyNodeParser->parseComponentTag(...));
        }

        throw ParseException::at('Missing closing tag ' . $closingTag, $this->cursor->offset());
    }

    /**
     * Parses one top-level node where an HTML doctype is accepted.
     */
    private function parseTopLevelNode(): Node
    {
        $literalNode = $this->templateLiteralParser->parse();
        if ($literalNode !== null) {
            return $literalNode;
        }

        if ($this->cursor->startsWith('<!')) {
            return new TextNode($this->htmlLiteralReader->readDoctype());
        }

        $tag = $this->tagParser->readOpenTag();

        return $this->bodyNodeParser->parseTopLevelTag($tag);
    }

    /**
     * Parses a child node while rejecting nested doctype declarations.
     *
     * @param Closure(OpenTag): Node $parseTag
     */
    private function parseNodeWithoutDoctype(Closure $parseTag): Node
    {
        $literalNode = $this->templateLiteralParser->parse();
        if ($literalNode !== null) {
            return $literalNode;
        }

        if ($this->cursor->startsWith('<!')) {
            throw ParseException::at('DOCTYPE is only allowed at top level', $this->cursor->offset());
        }

        $tag = $this->tagParser->readOpenTag();

        return $parseTag($tag);
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
