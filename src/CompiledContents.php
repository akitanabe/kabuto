<?php

declare(strict_types=1);

namespace Kabuto;

class CompiledContents
{
    public readonly string $addContents;
    public readonly string $restContents;

    public function __construct(string $addContents, string $restContents)
    {
        $this->addContents = $addContents;
        $this->restContents = $restContents;
    }
}
