<?php

declare(strict_types=1);

namespace Kabuto\Compilers;

use Kabuto\CompilingContents;

abstract class Compiler
{
    abstract public function compile(string $targetContents): CompilingContents;
    public array $uses;
}
