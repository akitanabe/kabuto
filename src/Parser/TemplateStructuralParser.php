<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\Node;
use Kabuto\Ast\TextNode;

final readonly class TemplateStructuralParser
{
    public function __construct(
        private SourceCursor $cursor,
        private TemplateLiteralParser $literalParser,
        private HtmlLiteralReader $htmlLiteralReader,
        private TagParser $tagParser,
        private BodyNodeParser $bodyNodeParser,
    ) {}

    public function parseNode(ParseBoundary $boundary): Node
    {
        $literalNode = $this->literalParser->parse();
        if ($literalNode !== null) {
            return $literalNode;
        }

        if ($this->cursor->startsWith('<!')) {
            if ($boundary->context !== TemplateParseContext::TopLevel) {
                throw ParseException::at('DOCTYPE is only allowed at top level', $this->cursor->offset());
            }

            return new TextNode($this->htmlLiteralReader->readDoctype());
        }

        $tag = $this->tagParser->readOpenTag();

        return match ($boundary->context) {
            TemplateParseContext::ComponentChildren => $this->bodyNodeParser->parseComponentTag($tag),
            TemplateParseContext::ConditionalComponentChildren => $this->bodyNodeParser->parseConditionalComponentTag(
                $tag,
            ),
            default => $this->bodyNodeParser->parseTopLevelTag($tag),
        };
    }

    public function parseClosingTag(string $expected): void
    {
        $startOffset = $this->cursor->offset();
        $this->cursor->expect('</');
        $actual = $this->cursor->readName();
        $this->cursor->skipWhitespace();
        $this->cursor->expect('>');

        if ($actual !== $expected) {
            throw ParseException::at('Expected closing tag ' . $expected . ', got ' . $actual, $startOffset);
        }
    }
}
