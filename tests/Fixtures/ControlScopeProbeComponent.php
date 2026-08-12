<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\RenderContext;
use UnexpectedValueException;

final class ControlScopeProbeComponent extends BaseComponent
{
    public string $value = '';

    public function render(RenderContext $context): string
    {
        $value = $this->prop('value');
        $attribute = $this->attribute('data-value');

        if (!is_string($value) || !is_string($attribute)) {
            throw new UnexpectedValueException('Control scope probe values must be strings.');
        }

        return (
            '['
            . $value
            . '|'
            . $attribute
            . '|'
            . $this->slot()?->render($context)
            . '|'
            . $this->slot('named')?->render($context)
            . ']'
        );
    }
}
