<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\Escaper;
use Kabuto\RenderContext;

final class TemplateAttributeProbeComponent extends BaseComponent
{
    public string $user = '';

    /**
     * Renders the component values visible through the public component contract.
     */
    public function render(RenderContext $context): string
    {
        return (
            '<section data-type="'
            . Escaper::escape($this->attribute('type', 'missing'))
            . '" data-user-attribute="'
            . Escaper::escape($this->attribute('user', 'missing'))
            . '">'
            . Escaper::escape($this->prop('user', 'missing'))
            . '|'
            . Escaper::escape($this->prop('unknown', 'missing'))
            . '|'
            . Escaper::escape($this->attribute('unknown', 'missing'))
            . '|'
            . $this->slot()?->render($context)
            . '</section>'
        );
    }
}
