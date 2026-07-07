<?php

declare(strict_types=1);

namespace Kabuto\Parser;

use Kabuto\Ast\AttributeNode;
use Kabuto\Ast\PropNode;

final readonly class OpenTag
{
    /**
     * Stores a parsed opening tag before its body is parsed.
     *
     * @param list<AttributeNode> $attributes
     * @param list<PropNode> $props
     */
    public function __construct(
        public string $name,
        public array $attributes,
        public array $props,
        public bool $selfClosing,
        public int $startOffset,
    ) {}
}
