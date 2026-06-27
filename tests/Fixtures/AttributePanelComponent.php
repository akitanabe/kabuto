<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\AttributeBag;
use Kabuto\BaseComponent;
use Kabuto\RenderContext;

final class AttributePanelComponent extends BaseComponent
{
    public string $title = '';

    /**
     * Renders accepted props and escaped caller attributes.
     */
    public function render(RenderContext $context): string
    {
        $title = $this->stringProp('title', 'missing');
        $id = $this->stringProp('id', 'no-prop');
        $extra = $this->stringProp('extra', 'ignored');

        return '<section' . new AttributeBag(['class' => 'panel'])
            ->merge($this->attributes())
            ->toHtml() . '>' . $title . '|' . $id . '|' . $extra . '</section>';
    }

    /**
     * Returns a string prop value for test output.
     */
    private function stringProp(string $name, string $default): string
    {
        $value = $this->prop($name, $default);

        return is_string($value) ? $value : $default;
    }
}
