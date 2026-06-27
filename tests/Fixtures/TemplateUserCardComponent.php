<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\Escaper;
use Kabuto\RenderContext;

final class TemplateUserCardComponent extends BaseComponent
{
    public string $user = '';

    /**
     * Renders the user prop inside an article element.
     */
    public function render(RenderContext $context): string
    {
        return '<article>' . Escaper::escape($this->prop('user')) . '</article>';
    }
}
