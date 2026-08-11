<?php

declare(strict_types=1);

namespace Kabuto;

use Kabuto\Diagnostics\SourceLocation;

final class OutputRenderer
{
    public function __construct(
        private OutputValueStringifier $stringifier = new OutputValueStringifier(),
        private UrlPolicy $urlPolicy = new UrlPolicy(),
    ) {}

    public function renderText(mixed $value, SourceLocation $location): string
    {
        return Escaper::escape($this->stringifier->stringify($value, $location));
    }

    public function renderDynamicAttribute(
        string $element,
        string $name,
        mixed $value,
        SourceLocation $location,
    ): string {
        $context = OutputContextPolicy::attribute($element, $name);
        if ($context === OutputContext::Forbidden || $context === OutputContext::Unsupported) {
            $status = $context === OutputContext::Forbidden ? 'forbidden' : 'unsupported';
            throw RenderException::atLocation('Dynamic attribute ' . $name . ' is ' . $status, $location);
        }

        if ($context !== OutputContext::HtmlAttribute && $context !== OutputContext::UrlAttribute) {
            throw RenderException::atLocation('Invalid output context for dynamic attribute ' . $name, $location);
        }

        if ($value === null || $value === false) {
            return '';
        }

        $string = $value === true ? true : $this->stringifier->stringify($value, $location);

        if ($context === OutputContext::UrlAttribute && $string !== true) {
            $this->urlPolicy->validate($string, $location);
        }

        return HtmlAttributeRenderer::render($name, $string);
    }
}
