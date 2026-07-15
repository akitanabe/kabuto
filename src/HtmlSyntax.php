<?php

declare(strict_types=1);

namespace Kabuto;

final class HtmlSyntax
{
    /** @var list<string> */
    private const array VOID_ELEMENTS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'source',
        'track',
        'wbr',
    ];

    /** @var list<string> */
    private const array RAW_TEXT_ELEMENTS = ['script', 'style', 'textarea', 'title'];

    /** @var list<string> */
    private const array BOOLEAN_ATTRIBUTES = [
        'allowfullscreen',
        'async',
        'autofocus',
        'autoplay',
        'checked',
        'controls',
        'default',
        'defer',
        'disabled',
        'formnovalidate',
        'hidden',
        'inert',
        'ismap',
        'itemscope',
        'loop',
        'multiple',
        'muted',
        'nomodule',
        'novalidate',
        'open',
        'playsinline',
        'readonly',
        'required',
        'reversed',
        'selected',
    ];

    /**
     * Returns whether the element does not accept child nodes or a closing tag.
     */
    public static function isVoidElement(string $name): bool
    {
        return in_array(strtolower($name), self::VOID_ELEMENTS, strict: true);
    }

    /**
     * Returns whether the element content must remain literal text.
     */
    public static function isRawTextElement(string $name): bool
    {
        return in_array(strtolower($name), self::RAW_TEXT_ELEMENTS, strict: true);
    }

    /**
     * Returns whether an empty value uses HTML's boolean attribute form.
     */
    public static function isBooleanAttribute(string $name): bool
    {
        return in_array(strtolower($name), self::BOOLEAN_ATTRIBUTES, strict: true);
    }
}
