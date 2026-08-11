<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\ElementNode;
use Kabuto\Ast\InterpolationNode;
use Kabuto\Ast\Node;
use Kabuto\HtmlSyntax;
use Kabuto\OutputContext;
use Kabuto\OutputContextPolicy;

final readonly class BodyNodeParser
{
    private ComponentParser $componentParser;

    private ElementAttributeParser $elementAttributeParser;

    /**
     * Stores collaborators used to convert open tags into body nodes.
     */
    public function __construct(
        private SourceCursor $cursor,
        private TemplateParser $templateParser,
        private ComponentPrefix $componentPrefix,
        private HtmlLiteralReader $htmlLiteralReader,
        private TextInterpolationParser $interpolationParser = new TextInterpolationParser(),
    ) {
        $this->componentParser = new ComponentParser($templateParser, $componentPrefix);
        $this->elementAttributeParser = new ElementAttributeParser($cursor, $componentPrefix);
    }

    /**
     * Parses a top-level opening tag where named slots are rejected.
     */
    public function parseTopLevelTag(OpenTag $tag): Node
    {
        if ($tag->name === $this->componentPrefix->slotTagName()) {
            if ($tag->selfClosing) {
                return $this->componentParser->parseSlotOutlet($tag);
            }

            throw ParseException::at('Named slots are only supported inside components', $this->cursor->offset());
        }

        return $this->parseRegularTag($tag);
    }

    /**
     * Parses a component child opening tag where named slots are accepted.
     */
    public function parseComponentTag(OpenTag $tag): Node
    {
        if ($tag->name === $this->componentPrefix->slotTagName()) {
            if ($tag->selfClosing) {
                return $this->componentParser->parseSlotOutlet($tag);
            }

            return $this->componentParser->parseNamedSlot($tag);
        }

        return $this->parseRegularTag($tag);
    }

    /**
     * Parses an opening tag as an HTML element or component.
     */
    private function parseRegularTag(OpenTag $tag): Node
    {
        if ($this->componentPrefix->matches($tag->name)) {
            return $this->componentParser->parseComponent($tag);
        }

        $dynamicAttributes = $this->elementAttributeParser->dynamic($tag);
        [$staticAttributes, $spread] = $this->elementAttributeParser->spread($tag);

        if ($tag->selfClosing || HtmlSyntax::isVoidElement($tag->name)) {
            return new ElementNode(
                $tag->name,
                $staticAttributes,
                dynamicAttributes: $dynamicAttributes,
                spread: $spread,
            );
        }

        if (HtmlSyntax::isRawTextElement($tag->name)) {
            $contentOffset = $this->cursor->offset();
            $content = $this->htmlLiteralReader->readRawTextUntilClosingTag($tag->name);
            $children = $this->interpolationParser->parse($content, $contentOffset, $this->cursor->source());

            if (OutputContextPolicy::text($tag->name) === OutputContext::Forbidden) {
                foreach ($children as $child) {
                    if ($child instanceof InterpolationNode) {
                        throw ParseException::at(
                            'Dynamic output is forbidden in ' . strtolower($tag->name) . ' content',
                            $child->expression()->offset(),
                        );
                    }
                }
            }

            return new ElementNode($tag->name, $staticAttributes, $children, $dynamicAttributes, $spread);
        }

        return new ElementNode(
            $tag->name,
            $staticAttributes,
            $this->templateParser->parseChildren($tag->name),
            $dynamicAttributes,
            $spread,
        );
    }
}
