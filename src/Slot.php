<?php

declare(strict_types=1);

namespace Kabuto;

use Closure;
use UnexpectedValueException;

final class Slot
{
    /**
     * Stores slot content that can be rendered later.
     *
     * @param string|Closure $content
     */
    public function __construct(
        private string|Closure $content,
    ) {}

    /**
     * Renders the slot content with the current context.
     */
    public function render(RenderContext $context): string
    {
        if ($this->content instanceof Closure) {
            $content = ($this->content)($context);

            if (!is_string($content)) {
                throw new UnexpectedValueException('Slot closures must return a string.');
            }

            return $content;
        }

        return $this->content;
    }
}
