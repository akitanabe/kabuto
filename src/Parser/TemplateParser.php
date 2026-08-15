<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\Node;

final class TemplateParser
{
    private TemplateLiteralParser $literalParser;

    private ControlDirectiveParser $directiveParser;

    private ControlBlockParser $controlBlockParser;

    private TemplateStructuralParser $structuralParser;

    /**
     * Stores parser collaborators for a single source cursor.
     */
    public function __construct(
        private readonly SourceCursor $cursor,
        ComponentPrefix $componentPrefix,
    ) {
        $htmlLiteralReader = new HtmlLiteralReader($cursor);
        $this->directiveParser = new ControlDirectiveParser($cursor);
        $this->literalParser = new TemplateLiteralParser($cursor, $htmlLiteralReader, $this->directiveParser);
        $bodyNodeParser = new BodyNodeParser($cursor, $this, $componentPrefix, $htmlLiteralReader);
        $this->controlBlockParser = new ControlBlockParser($this->directiveParser);
        $this->structuralParser = new TemplateStructuralParser(
            $cursor,
            $this->literalParser,
            $htmlLiteralReader,
            new TagParser($cursor),
            $bodyNodeParser,
        );
    }

    /**
     * Parses all top-level nodes.
     *
     * @return list<Node>
     */
    public function parse(): array
    {
        [$nodes] = $this->parseSequence(ParseBoundary::topLevel());

        return $nodes;
    }

    /**
     * Parses children until the requested closing tag is reached.
     *
     * @return list<Node>
     */
    public function parseChildren(string $closingTag): array
    {
        [$nodes] = $this->parseSequence(new ParseBoundary($closingTag, TemplateParseContext::Body));

        return $nodes;
    }

    /**
     * Parses component children where direct named slots are accepted.
     *
     * @return list<Node>
     */
    public function parseComponentChildren(string $closingTag): array
    {
        [$nodes] = $this->parseSequence(new ParseBoundary($closingTag, TemplateParseContext::ComponentChildren));

        return $nodes;
    }

    /**
     * @param list<string> $stopTokens
     * @return array{list<Node>, ?string}
     */
    private function parseSequence(ParseBoundary $boundary, array $stopTokens = []): array
    {
        $nodes = [];

        while ($this->literalParser->hasInput()) {
            if ($this->literalParser->isAtClosingTag()) {
                if ($stopTokens !== []) {
                    throw ParseException::at(
                        'Expected @'
                        . $this->closingToken($stopTokens)
                        . ' before closing tag '
                        . ($boundary->closingTag ?? 'at top level'),
                        $this->cursor->offset(),
                    );
                }

                if ($boundary->closingTag === null) {
                    throw ParseException::at('Unexpected closing tag', $this->cursor->offset());
                }

                $this->structuralParser->parseClosingTag($boundary->closingTag);

                return [$nodes, null];
            }

            $keyword = $this->literalParser->hasPendingNodes() ? null : $this->directiveParser->currentKeyword();

            if ($keyword !== null) {
                if (in_array($keyword, $stopTokens, strict: true)) {
                    return [$nodes, $keyword];
                }

                if ($keyword === 'if' || $keyword === 'foreach') {
                    $nodes[] = $this->controlBlockParser->parse($keyword, $boundary, $this->parseSequence(...));
                    continue;
                }

                $message = $stopTokens === []
                    ? 'Unexpected @' . $keyword
                    : 'Expected @' . $this->closingToken($stopTokens) . ', got @' . $keyword;
                throw ParseException::at($message, $this->cursor->offset());
            }

            $nodes[] = $this->structuralParser->parseNode($boundary);
        }

        if ($stopTokens !== []) {
            throw ParseException::at('Missing @' . $this->closingToken($stopTokens), $this->cursor->offset());
        }

        if ($boundary->closingTag !== null) {
            throw ParseException::at('Missing closing tag ' . $boundary->closingTag, $this->cursor->offset());
        }

        return [$nodes, null];
    }

    /** @param list<string> $stopTokens */
    private function closingToken(array $stopTokens): string
    {
        return $stopTokens[count($stopTokens) - 1];
    }
}
