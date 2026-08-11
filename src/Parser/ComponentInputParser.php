<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\DynamicAttributeNode;
use Kabuto\Ast\PropNode;

final class ComponentInputParser
{
    /** @return array{0: list<PropNode>, 1: list<DynamicAttributeNode>} */
    public function parse(OpenTag $tag): array
    {
        $props = [];
        $attributes = [];

        foreach ($tag->props as $prop) {
            $name = strtolower($prop->name());
            if ($name === 'attributes') {
                throw ParseException::at(
                    'The :attributes prop is reserved for the component attribute binding',
                    $prop->expressionData()->offset(),
                );
            }

            if (!str_starts_with($name, 'attr:')) {
                $props[] = $prop;
                continue;
            }

            $attributeName = substr($prop->name(), strlen('attr:'));
            if ($attributeName === '') {
                throw ParseException::at(
                    'Dynamic component attribute name must not be empty',
                    $prop->expressionData()->offset(),
                );
            }

            $attributes[] = new DynamicAttributeNode($attributeName, $prop->expressionData(), $prop->position());
        }

        return [$props, $attributes];
    }
}
