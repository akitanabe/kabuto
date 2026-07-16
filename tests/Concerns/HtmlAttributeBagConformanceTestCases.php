<?php

declare(strict_types=1);

namespace Kabuto\Tests\Concerns;

use Kabuto\AttributeBag;
use PHPUnit\Framework\Attributes\Test;

trait HtmlAttributeBagConformanceTestCases
{
    #[Test]
    public function attributeBagOmitsFalseAndNullAndNormalizesBooleanEmptyValues(): void
    {
        $attributes = new AttributeBag([
            'required' => '',
            'disabled' => '',
            'hidden' => '',
            'class' => '',
            'data-state' => '',
            'checked' => true,
            'aria-hidden' => false,
            'id' => null,
            'title' => 'Save & close',
        ]);

        self::assertSame(
            ' required disabled hidden class="" data-state="" checked title="Save &amp; close"',
            $attributes->toHtml(),
        );
    }
}
