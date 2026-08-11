<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\RenderContext;

final class ForwardingViewComponent extends BaseComponent
{
    public string $label = '';

    public function render(RenderContext $context): string
    {
        return $this->view('forwarding-view.kbt', ['label' => $this->prop('label', '')], $context);
    }
}
