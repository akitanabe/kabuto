<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\RenderContext;

final class ComponentTemplateComponent extends BaseComponent
{
    public string $name = '';

    /**
     * Renders this component through its template file.
     */
    public function render(RenderContext $context): string
    {
        return $this->view(
            'component-template.kabuto',
            [
                'name' => $this->prop('name'),
            ],
            $context,
        );
    }
}
