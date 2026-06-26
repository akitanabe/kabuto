<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\ComponentNode;
use Kabuto\Ast\NamedSlotNode;
use Kabuto\Ast\SlotOutletNode;

final readonly class ComponentParser
{
    /**
     * Stores the template parser used to parse component bodies.
     */
    public function __construct(
        private TemplateParser $templateParser,
        private ComponentPrefix $componentPrefix,
    ) {}

    /**
     * Parses a component body and separates default and named slot children.
     */
    public function parseComponent(OpenTag $tag): ComponentNode
    {
        $componentName = $this->componentPrefix->removeFrom($tag->name);
        if ($componentName === '') {
            throw ParseException::at('Component name must not be empty', 0);
        }

        if ($tag->selfClosing) {
            return new ComponentNode($componentName, $tag->attributes, $tag->props);
        }

        $children = [];
        $slots = [];

        foreach ($this->templateParser->parseComponentChildren($tag->name) as $child) {
            if ($child instanceof NamedSlotNode) {
                $slots[$child->name()] = $child->children();
                continue;
            }

            $children[] = $child;
        }

        return new ComponentNode($componentName, $tag->attributes, $tag->props, $children, $slots);
    }

    /**
     * Parses a named slot and validates its required name attribute.
     */
    public function parseNamedSlot(OpenTag $tag): NamedSlotNode
    {
        if ($tag->props !== []) {
            throw ParseException::at('Dynamic props are not supported on named slots', 0);
        }

        if ($tag->selfClosing) {
            throw ParseException::at('Named slots must have content', 0);
        }

        if (count($tag->attributes) !== 1 || $tag->attributes[0]->name() !== 'name') {
            throw ParseException::at('Named slots require a name attribute', 0);
        }

        return new NamedSlotNode(
            $tag->attributes[0]->value(),
            $this->templateParser->parseChildren($this->componentPrefix->slotTagName()),
        );
    }

    /**
     * Parses a self-closing slot tag into a slot outlet.
     */
    public function parseSlotOutlet(OpenTag $tag): SlotOutletNode
    {
        if ($tag->props !== []) {
            throw ParseException::at('Dynamic props are not supported on slot outlets', 0);
        }

        if (!$tag->selfClosing) {
            throw ParseException::at('Slot outlets must be self-closing', 0);
        }

        if ($tag->attributes === []) {
            return new SlotOutletNode(null);
        }

        if (count($tag->attributes) !== 1 || $tag->attributes[0]->name() !== 'name') {
            throw ParseException::at('Slot outlets only support a name attribute', 0);
        }

        return new SlotOutletNode($tag->attributes[0]->value());
    }
}
