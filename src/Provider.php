<?php

declare(strict_types=1);

namespace Kabuto;

use InvalidArgumentException;

final class Provider extends BaseComponent
{
    /**
     * Renders the default slot with one context value provided.
     */
    public function render(RenderContext $context): string
    {
        $name = $this->prop('name');

        if (!is_string($name)) {
            throw new InvalidArgumentException('Provider name must be a string.');
        }

        $slot = $this->slot();

        if ($slot === null) {
            throw new InvalidArgumentException('Provider requires a default slot.');
        }

        return $slot->render($context->with($name, $this->prop('value')));
    }
}
