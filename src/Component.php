<?php

declare(strict_types=1);

namespace Kabuto;

interface Component
{
    /**
     * Renders the component with the current render context.
     */
    public function render(RenderContext $context): string;
}
