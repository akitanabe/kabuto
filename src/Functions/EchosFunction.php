<?php

declare(strict_types=1);

namespace Kabuto\Functions\Echos;

function h(mixed $var): string
{
    return htmlspecialchars((string) $var, encoding: mb_internal_encoding());
}

function s(mixed $var): string
{
    return addslashes((string) $var);
}
