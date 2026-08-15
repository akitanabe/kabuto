<?php

declare(strict_types=1);

namespace Kabuto\Tests\Fixtures;

use Kabuto\BaseComponent;
use Kabuto\RenderContext;
use Kabuto\Slot;

final class DeferredControlSlotsComponent extends BaseComponent
{
    /** @var list<array{?Slot, ?Slot}> */
    private static array $captured = [];

    public static function resetCaptured(): void
    {
        self::$captured = [];
    }

    public static function renderCaptured(RenderContext $context): string
    {
        $html = '';

        foreach (self::$captured as [$default, $named]) {
            $html .= '[' . $default?->render($context) . '|' . $named?->render($context) . ']';
        }

        return $html;
    }

    public function render(RenderContext $context): string
    {
        self::$captured[] = [$this->slot(), $this->slot('named')];

        return '';
    }
}
