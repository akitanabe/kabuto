<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\RenderContext;

final class TemplateLayoutComponent extends BaseComponent
{
    /**
     * Renders the named header slot followed by default content.
     */
    public function render(RenderContext $context): string
    {
        return (
            '<header>'
            . $this->slot('header')?->render($context)
            . '</header>'
            . '<main>'
            . $this->slot()?->render($context)
            . '</main>'
        );
    }
}
