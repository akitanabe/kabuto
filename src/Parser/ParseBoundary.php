<?php

declare(strict_types=1);

namespace Kabuto\Parser;

final readonly class ParseBoundary
{
    public function __construct(
        public ?string $closingTag,
        public TemplateParseContext $context,
    ) {}

    public static function topLevel(): self
    {
        return new self(null, TemplateParseContext::TopLevel);
    }

    public function insideControl(): self
    {
        return new self($this->closingTag, $this->context->insideControl());
    }
}
