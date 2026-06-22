<?php

declare(strict_types=1);

namespace Kabuto;

final class Kabuto
{
    /**
     * Returns the package version exposed by this development skeleton.
     */
    public function version(): string
    {
        return '0.1.0';
    }

    /**
     * Renders a component synchronously with the provided context.
     */
    public function render(Component $component, ?RenderContext $context = null): string
    {
        return $component->render($context ?? new RenderContext());
    }
}
