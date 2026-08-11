<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\DynamicAttributeNode;
use Kabuto\Ast\SpreadAttributeNode;
use Kabuto\OutputContext;
use Kabuto\OutputContextPolicy;

final readonly class ElementAttributeParser
{
    public function __construct(
        private SourceCursor $cursor,
        private ComponentPrefix $componentPrefix,
        private ExpressionParser $expressionParser = new ExpressionParser(),
    ) {}

    /** @return list<DynamicAttributeNode> */
    public function dynamic(OpenTag $tag): array
    {
        $attributes = [];

        foreach ($tag->props as $prop) {
            if (str_starts_with(strtolower($prop->name()), 'attr:')) {
                throw ParseException::at(
                    'The :attr:* namespace is reserved for component caller attributes',
                    $prop->expressionData()->offset(),
                );
            }

            $context = OutputContextPolicy::attribute($tag->name, $prop->name());
            if ($context === OutputContext::Forbidden || $context === OutputContext::Unsupported) {
                $status = $context === OutputContext::Forbidden ? 'forbidden' : 'unsupported';
                throw ParseException::at(
                    'Dynamic attribute ' . $prop->name() . ' is ' . $status,
                    $prop->expressionData()->offset(),
                );
            }

            $attributes[] = new DynamicAttributeNode($prop->name(), $prop->expressionData(), $prop->position());
        }

        return $attributes;
    }

    /** @return array{0: list<AttributeNode>, 1: ?SpreadAttributeNode} */
    public function spread(OpenTag $tag): array
    {
        $attributes = [];
        $spread = null;
        $spreadName = strtolower($this->componentPrefix->attributesAttributeName());

        foreach ($tag->attributes as $attribute) {
            if (strtolower($attribute->name()) !== $spreadName) {
                $attributes[] = $attribute;
                continue;
            }

            $location = $attribute->valueLocation() ?? $attribute->location();
            if ($spread !== null) {
                $nameLocation = $attribute->location();
                throw ParseException::at(
                    'Only one attribute spread is allowed on an element',
                    $nameLocation === null ? $tag->startOffset : $nameLocation->offset,
                );
            }

            if ($attribute->isBare() || $location === null) {
                throw ParseException::at('Attribute spread requires an expression value', $tag->startOffset);
            }

            $spread = new SpreadAttributeNode($this->expressionParser->parse(
                $attribute->value(),
                $location->offset,
                $this->cursor->source(),
            ));
        }

        return [$attributes, $spread];
    }
}
