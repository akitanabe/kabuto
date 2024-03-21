<?php

declare(strict_types=1);

namespace Kabuto\Compilers;

use Kabuto\CompilingTemplate;

abstract class Compiler
{
    abstract public function compile(
        CompilingTemplate $template,
    ): CompilingTemplate;
    public array $uses;
}
