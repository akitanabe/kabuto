<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\Escaper;
use Kabuto\RenderContext;

final class RegistryMessageComponent extends BaseComponent
{
    /**
     * Renders the message prop as escaped text.
     */
    public function render(RenderContext $context): string
    {
        return Escaper::escape($this->prop('message'));
    }
}
