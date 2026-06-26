<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\RenderContext;

final class ComponentTemplateLayoutComponent extends BaseComponent
{
    /**
     * Renders received slots through this component's template file.
     */
    public function render(RenderContext $context): string
    {
        return $this->view('component-layout', context: $context);
    }
}
