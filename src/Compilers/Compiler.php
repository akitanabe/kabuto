<?php

declare(strict_types=1);

namespace Kabuto\Compilers;

use Kabuto\CompiledContents;

abstract class Compiler
{
    abstract public function compile(string $targetContents): CompiledContents;
    public array $uses;
}
