<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use InvalidArgumentException;
use Kabuto\Escaper;
use Kabuto\Provider;
use Kabuto\RenderContext;
use Kabuto\Slot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProviderTest extends TestCase
{
    /**
     * Confirms that a provider renders its default slot with an extended context.
     */
    #[Test]
    public function providerRendersDefaultSlotWithExtendedContext(): void
    {
        $context = new RenderContext(['theme' => 'light']);
        $provider = new Provider(props: [
            'name' => 'theme',
            'value' => 'dark',
        ], slot: new Slot(static fn(RenderContext $context): string => 'Theme: ' . Escaper::escape($context->get('theme'))));

        self::assertSame('Theme: dark', $provider->render($context));
        self::assertSame('light', $context->get('theme'));
    }

    /**
     * Confirms that provider context values do not leak outside its render.
     */
    #[Test]
    public function providerDoesNotLeakProvidedValueOutsideRender(): void
    {
        $context = new RenderContext();
        $provider = new Provider(
            props: ['name' => 'locale', 'value' => 'ja'],
            slot: new Slot(static fn(RenderContext $context): string => Escaper::escape($context->get('locale'))),
        );

        self::assertSame('ja', $provider->render($context));
        self::assertNull($context->get('locale'));
    }

    /**
     * Confirms that provider names must be strings.
     */
    #[Test]
    public function providerRejectsNonStringName(): void
    {
        $provider = new Provider(props: ['name' => 123, 'value' => 'dark'], slot: new Slot('Body'));

        $this->expectException(InvalidArgumentException::class);

        $provider->render(new RenderContext());
    }

    /**
     * Confirms that providers require a default slot.
     */
    #[Test]
    public function providerRejectsMissingDefaultSlot(): void
    {
        $provider = new Provider(props: ['name' => 'theme', 'value' => 'dark']);

        $this->expectException(InvalidArgumentException::class);

        $provider->render(new RenderContext());
    }
}
