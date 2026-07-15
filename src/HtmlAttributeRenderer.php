<?php

declare(strict_types=1);

namespace Kabuto;

use Kabuto\Ast\AttributeNode;

final class HtmlAttributeRenderer
{
    /**
     * Renders an attribute according to the HTML serialization rules.
     */
    public static function render(string $name, mixed $value): string
    {
        return match (true) {
            $value === false, $value === null => '',
            $value === true, $value === '' && HtmlSyntax::isBooleanAttribute($name) => ' ' . $name,
            default => ' ' . $name . '="' . Escaper::escape($value) . '"',
        };
    }

    /**
     * Renders a parsed static attribute, preserving bare-attribute semantics.
     */
    public static function renderStatic(AttributeNode $attribute): string
    {
        return self::render($attribute->name(), $attribute->isBare() ? true : $attribute->value());
    }
}
