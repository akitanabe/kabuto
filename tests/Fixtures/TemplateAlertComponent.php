<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\Escaper;
use Kabuto\RenderContext;

final class TemplateAlertComponent extends BaseComponent
{
    /**
     * Renders an alert with an escaped type attribute and default slot content.
     */
    public function render(RenderContext $context): string
    {
        return (
            '<aside data-type="'
            . Escaper::escape($this->attribute('type'))
            . '">'
            . $this->slot()?->render($context)
            . '</aside>'
        );
    }
}
