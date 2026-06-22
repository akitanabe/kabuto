<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use InvalidArgumentException;
use Kabuto\BaseComponent;
use Kabuto\Component;
use Kabuto\Escaper;
use Kabuto\Kabuto;
use Kabuto\RenderContext;
use Kabuto\Slot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class RuntimeTest extends TestCase
{
    /**
     * Confirms that the engine renders a PHP component synchronously.
     */
    #[Test]
    public function engineRendersComponentSynchronously(): void
    {
        $component = new class(['name' => '<Alice>']) extends BaseComponent {
            /**
             * Renders a greeting with escaped prop and context values.
             */
            public function render(RenderContext $context): string
            {
                return (
                    '<p>'
                    . Escaper::escape($this->prop('name'))
                    . ' in '
                    . Escaper::escape($context->get('place'))
                    . '</p>'
                );
            }
        };

        self::assertSame('<p>&lt;Alice&gt; in R&amp;D</p>', new Kabuto()->render($component, new RenderContext([
            'place' => 'R&D',
        ])));
    }

    /**
     * Confirms that render context values are copied when extended.
     */
    #[Test]
    public function renderContextWithReturnsExtendedCopy(): void
    {
        $context = new RenderContext(['theme' => 'light']);
        $next = $context->with('theme', 'dark')->with('locale', 'ja');

        self::assertSame('light', $context->get('theme'));
        self::assertNull($context->get('locale'));
        self::assertSame('dark', $next->get('theme'));
        self::assertSame('ja', $next->get('locale'));
    }

    /**
     * Confirms that components can render default and named slots.
     */
    #[Test]
    public function baseComponentExposesDefaultAndNamedSlots(): void
    {
        $component = new class(
            slot: new Slot('Body'),
            slots: [
                'header' => new Slot(
                    static fn(RenderContext $context): string => 'Header: ' . Escaper::escape($context->get('title')),
                ),
            ],
        ) extends BaseComponent {
            /**
             * Renders its named header slot followed by its default slot.
             */
            public function render(RenderContext $context): string
            {
                return $this->slot('header')?->render($context) . ' / ' . $this->slot()?->render($context);
            }
        };

        self::assertSame('Header: Welcome / Body', $component->render(new RenderContext(['title' => 'Welcome'])));
    }

    /**
     * Confirms that escaping follows HTML text escaping rules.
     */
    #[Test]
    public function escaperEscapesHtmlSpecialCharacters(): void
    {
        self::assertSame(
            '&lt;span title=&quot;Tom&#039;s&quot;&gt;&amp;&lt;/span&gt;',
            Escaper::escape('<span title="Tom\'s">&</span>'),
        );
    }

    /**
     * Confirms that unsupported values are rejected before escaping.
     */
    #[Test]
    public function escaperRejectsUnsupportedValues(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Escaper::escape(['message' => 'hello']);
    }

    /**
     * Confirms that dynamic slot content must render to text.
     */
    #[Test]
    public function slotRejectsNonStringClosureResult(): void
    {
        $slot = new Slot(static fn(RenderContext $context): int => 123);

        $this->expectException(UnexpectedValueException::class);

        $slot->render(new RenderContext());
    }

    /**
     * Confirms that the public component contract accepts a render context.
     */
    #[Test]
    public function componentContractRendersWithContext(): void
    {
        $component = new class implements Component {
            /**
             * Renders a context value through the public component contract.
             */
            public function render(RenderContext $context): string
            {
                return Escaper::escape($context->get('message'));
            }
        };

        self::assertSame('hello', $component->render(new RenderContext(['message' => 'hello'])));
    }
}
