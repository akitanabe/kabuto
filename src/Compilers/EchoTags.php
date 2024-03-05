<?php

declare(strict_types=1);

namespace Kabuto\Compilers;

class EchoTags
{
    public readonly string $quoteOpenTag;
    public readonly string $quoteCloseTag;

    public function __construct(
        public readonly string $openTag,
        public readonly string $closeTag,
        public readonly string $format,
    ) {
        $this->quoteOpenTag = preg_quote($openTag);
        $this->quoteCloseTag = preg_quote($closeTag);
    }
}
