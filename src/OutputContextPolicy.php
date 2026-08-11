<?php

declare(strict_types=1);

namespace Kabuto;

final class OutputContextPolicy
{
    /** @var array<string, list<string>|null> */
    private const array URL_ATTRIBUTES = [
        'href' => null,
        'src' => null,
        'action' => null,
        'formaction' => null,
        'poster' => null,
        'cite' => null,
        'itemid' => null,
        'usemap' => null,
        'data' => ['object'],
    ];

    public static function text(string $element): OutputContext
    {
        return match (strtolower($element)) {
            'script', 'style' => OutputContext::Forbidden,
            default => OutputContext::HtmlText,
        };
    }

    public static function attribute(string $element, string $attribute): OutputContext
    {
        $normalized = strtolower($attribute);

        if (str_starts_with($normalized, 'on') || in_array($normalized, ['style', 'srcdoc'], strict: true)) {
            return OutputContext::Forbidden;
        }

        if (in_array($normalized, ['srcset', 'ping', 'xlink:href'], strict: true)) {
            return OutputContext::Unsupported;
        }

        if (!array_key_exists($normalized, self::URL_ATTRIBUTES)) {
            return OutputContext::HtmlAttribute;
        }

        $elements = self::URL_ATTRIBUTES[$normalized];

        return $elements === null || in_array(strtolower($element), $elements, strict: true)
            ? OutputContext::UrlAttribute
            : OutputContext::HtmlAttribute;
    }
}
