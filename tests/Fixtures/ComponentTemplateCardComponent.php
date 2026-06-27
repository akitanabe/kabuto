<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\Escaper;
use Kabuto\RenderContext;

final class ComponentTemplateCardComponent extends BaseComponent
{
    public string $name = '';

    /**
     * Renders a card using both template data and the current render context.
     */
    public function render(RenderContext $context): string
    {
        return (
            '<article>'
            . Escaper::escape($this->prop('name'))
            . ' in '
            . Escaper::escape($context->get('place'))
            . '</article>'
        );
    }
}
