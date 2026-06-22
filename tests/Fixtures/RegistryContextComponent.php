<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\RenderContext;

final class RegistryContextComponent extends BaseComponent
{
    /**
     * Renders the locale value from the current context.
     */
    public function render(RenderContext $context): string
    {
        $locale = $context->get('locale');

        if (!is_string($locale)) {
            return '';
        }

        return $locale;
    }
}
