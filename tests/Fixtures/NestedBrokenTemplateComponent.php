<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\RenderContext;

final class NestedBrokenTemplateComponent extends BaseComponent
{
    /**
     * Renders an inner template fixture that intentionally contains invalid syntax.
     */
    public function render(RenderContext $context): string
    {
        return $this->view('nested-broken.kbt', context: $context);
    }
}
