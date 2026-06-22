<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\Escaper;
use Kabuto\RenderContext;

final class RegistryAlertComponent extends BaseComponent
{
    /**
     * Renders an alert with escaped props and slots.
     */
    public function render(RenderContext $context): string
    {
        return (
            '<aside data-kind="'
            . Escaper::escape($this->prop('kind'))
            . '"><h2>'
            . $this->slot('title')?->render($context)
            . '</h2>'
            . $this->slot()?->render($context)
            . '</aside>'
        );
    }
}
