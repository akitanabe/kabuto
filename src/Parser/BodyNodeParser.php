<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\ElementNode;
use Kabuto\Ast\Node;

final readonly class BodyNodeParser
{
    private ComponentParser $componentParser;

    /**
     * Stores collaborators used to convert open tags into body nodes.
     */
    public function __construct(
        private SourceCursor $cursor,
        private TemplateParser $templateParser,
        private ComponentPrefix $componentPrefix,
    ) {
        $this->componentParser = new ComponentParser($templateParser, $componentPrefix);
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

        if ($tag->props !== []) {
            throw ParseException::at('Dynamic props are only supported on components', $this->cursor->offset());
        }

        if ($tag->selfClosing) {
            return new ElementNode($tag->name, $tag->attributes);
        }

        return new ElementNode($tag->name, $tag->attributes, $this->templateParser->parseChildren($tag->name));
    }
}
