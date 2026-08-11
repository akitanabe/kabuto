<?php

declare(strict_types=1);

namespace Kabuto\Compiler;

use Kabuto\Diagnostics\SourceLocation;

final class PhpSource
{
    public static function string(string $value): string
    {
        return var_export($value, return: true);
    }

    public static function location(?SourceLocation $location): string
    {
        return $location === null
            ? 'null'
            : 'new \\Kabuto\\Diagnostics\\SourceLocation(...'
            . var_export([
                'offset' => $location->offset,
                'line' => $location->line,
                'byteColumn' => $location->byteColumn,
                'templateName' => $location->templateName,
            ], return: true)
            . ')';
    }
}
