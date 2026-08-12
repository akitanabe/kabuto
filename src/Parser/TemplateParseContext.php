<?php

declare(strict_types=1);

namespace Kabuto\Parser;

enum TemplateParseContext
{
    case TopLevel;
    case Body;
    case ComponentChildren;
    case ConditionalComponentChildren;

    public function insideControl(): self
    {
        return match ($this) {
            self::ComponentChildren, self::ConditionalComponentChildren => self::ConditionalComponentChildren,
            default => self::Body,
        };
    }
}
