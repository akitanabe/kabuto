<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\AttributeBag;
use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\TemplateEngine;
use Kabuto\TemplateLoader;
use Kabuto\Tests\Fixtures\ControlScopeProbeComponent;
use Kabuto\Tests\Fixtures\DeferredControlSlotsComponent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

final class ControlSyntaxScopeTest extends ControlSyntaxTestCase
{
    #[Test]
    public function loopBindingReachesEveryCallerSideOutputSurface(): void
    {
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'probe' => ControlScopeProbeComponent::class,
        ]));
        $engine = new TemplateEngine($renderer);
        $componentTemplate =
            '@foreach ($items as $item)<k-probe :value="$item" :attr:data-value="$item">'
            . '{{ $item }}<k-slot name="named"><i>{{ $item }}</i></k-slot>'
            . '</k-probe>@endforeach';

        $this->assertDirectAndCompiled(
            $engine,
            $componentTemplate,
            ['items' => ['A&B']],
            '[A&B|A&B|A&amp;B|<i>A&amp;B</i>]',
            $renderer,
        );
        $this->assertDirectAndCompiled(
            $this->engine(),
            '@foreach ($items as $item)<a :title="$item" :href="$item">{{ $item }}</a>@endforeach',
            ['items' => ['a&b']],
            '<a title="a&amp;b" href="a&amp;b">a&amp;b</a>',
        );
        $this->assertDirectAndCompiled(
            $this->engine(),
            '@foreach ($items as $item)<div class="base" k-attributes="$item"></div>@endforeach',
            ['items' => [new AttributeBag(['id' => 'one'])]],
            '<div class="base" id="one"></div>',
        );
        $this->assertDirectAndCompiled(
            $this->engine(),
            '@foreach ($items as $item)<div :class="$item" k-attributes="$attributes"></div>@endforeach',
            ['items' => ['local'], 'attributes' => new AttributeBag(['id' => 'one'])],
            '<div class="local" id="one"></div>',
        );
    }

    #[Test]
    public function loopBindingDoesNotImplicitlyCrossAComponentViewBoundary(): void
    {
        $renderer = new ComponentRenderer(new ComponentRegistry());
        $engine = new TemplateEngine($renderer, loader: new TemplateLoader(__DIR__ . '/Fixtures/templates'));
        $template = '@foreach ($items as $item)<k-control-scope :value="$item" />@endforeach';

        $this->assertDirectAndCompiled($engine, $template, ['items' => ['visible']], "|visible\n", $renderer);
    }

    #[Test]
    public function controlBlocksRenderInsideDefaultAndNamedSlotBodies(): void
    {
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'probe' => ControlScopeProbeComponent::class,
        ]));
        $engine = new TemplateEngine($renderer);
        $template =
            '<k-probe :value="$value" :attr:data-value="$value">'
            . '@if ($ok)default@endif'
            . '<k-slot name="named">@foreach ($items as $item){{ $item }}@endforeach</k-slot>'
            . '</k-probe>';

        $this->assertDirectAndCompiled(
            $engine,
            $template,
            [
                'value' => 'scope',
                'ok' => true,
                'items' => ['a', 'b'],
            ],
            '[scope|scope|default|ab]',
            $renderer,
        );
    }

    #[Test]
    #[TestWith(['direct'])]
    #[TestWith(['compiled'])]
    public function slotsCaptureEachIterationCallerScopeByValue(string $mode): void
    {
        DeferredControlSlotsComponent::resetCaptured();
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'deferred' => DeferredControlSlotsComponent::class,
        ]));
        $engine = new TemplateEngine($renderer);
        $template =
            '@foreach ($items as $item)<k-deferred>'
            . '{{ $item }}<k-slot name="named"><b>{{ $item }}</b></k-slot>'
            . '</k-deferred>@endforeach';

        $output = $mode === 'direct'
            ? $engine->render($template, ['items' => ['A&B', 'C']])
            : $this->renderCompiled($engine, $renderer, $template, ['items' => ['A&B', 'C']]);

        self::assertSame('', $output);
        self::assertSame(
            '[A&amp;B|<b>A&amp;B</b>][C|<b>C</b>]',
            DeferredControlSlotsComponent::renderCaptured(new \Kabuto\RenderContext()),
        );
        DeferredControlSlotsComponent::resetCaptured();
    }

    #[Test]
    public function loopBindingsRenderNormallyForRendererContextAndScopeNames(): void
    {
        $template =
            '@foreach ($values as $renderer){{ $renderer }}@endforeach'
            . '@foreach ($values as $context){{ $context }}@endforeach'
            . '@foreach ($values as $scope){{ $scope }}@endforeach'
            . '|done';

        $this->assertDirectAndCompiled($this->engine(), $template, ['values' => ['x']], 'xxx|done');
    }
}
