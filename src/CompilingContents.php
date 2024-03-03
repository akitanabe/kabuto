<?php

declare(strict_types=1);

namespace Kabuto;

class CompilingContents
{
    public readonly string $addContents;
    public readonly string $restContents;

    /**
     * array{string, string} $contents
     *
     */
    public function __construct(array $contents)
    {
        [$addContents, $restContents] = $contents;
        $this->addContents = $addContents;
        $this->restContents = $restContents;
    }
}
