<?php

declare(strict_types=1);

namespace Kabuto;

use Closure;

class CompilingContents
{
    public readonly string $addContents;
    public readonly string $restContents;

    public readonly ?Closure $todo;

    /**
     * array{string, string} $contents
     * ?Closure $todo
     *
     */
    public function __construct(
        array $contents,
        ?Closure $todo = null,
    ){
        [$addContents, $restContents] = $contents;
        $this->addContents = $addContents;
        $this->restContents = $restContents;

        $this->todo = $todo;
    }

    public function todo(string $targetContents): ?CompilingContents
    {
        $todo = $this->todo;
        return isset($todo) ? $todo($targetContents) : null;
    }
}
