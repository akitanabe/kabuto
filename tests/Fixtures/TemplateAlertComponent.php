<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\Escaper;
use Kabuto\RenderContext;

final class TemplateAlertComponent extends BaseComponent
{
    /**
     * Renders an alert with an escaped type prop and default slot content.
     */
    public function render(RenderContext $context): string
    {
        return (
            '<aside data-type="'
            . Escaper::escape($this->prop('type'))
            . '">'
            . $this->slot()?->render($context)
            . '</aside>'
        );
    }
}
