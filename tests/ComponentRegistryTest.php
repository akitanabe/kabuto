<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use InvalidArgumentException;
use Kabuto\ComponentRegistry;
use Kabuto\ComponentRenderer;
use Kabuto\RenderContext;
use Kabuto\Slot;
use Kabuto\Tests\Fixtures\RegistryAlertComponent;
use Kabuto\Tests\Fixtures\RegistryContextComponent;
use Kabuto\Tests\Fixtures\RegistryMessageFactory;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class ComponentRegistryTest extends TestCase
{
    /**
     * Confirms that a registered class-string is instantiated with props and slots.
     */
    public function testRegistryResolvesClassStringComponent(): void
    {
        $registry = new ComponentRegistry([
            'alert' => RegistryAlertComponent::class,
        ]);

        $component = $registry->resolve('alert', ['kind' => 'info'], new Slot('Saved'), [
            'title' => new Slot('Notice'),
        ]);

        self::assertInstanceOf(RegistryAlertComponent::class, $component);
        self::assertSame(
            '<aside data-kind="info"><h2>Notice</h2>Saved</aside>',
            $component->render(new RenderContext()),
        );
    }

    /**
     * Confirms that a registered factory can create a component.
     */
    public function testRegistryResolvesFactoryComponent(): void
    {
        $registry = new ComponentRegistry([
            'message' => new RegistryMessageFactory(),
        ]);

        $component = $registry->resolve('message', ['message' => '<Hello>']);

        self::assertSame('&lt;Hello&gt;', $component->render(new RenderContext()));
    }

    /**
     * Confirms that unknown component names fail explicitly.
     */
    public function testRegistryRejectsUnknownComponentName(): void
    {
        $registry = new ComponentRegistry();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Component is not registered: missing');

        $registry->resolve('missing');
    }

    /**
     * Confirms that factories must return a component instance.
     */
    public function testRegistryRejectsFactoryThatDoesNotReturnComponent(): void
    {
        $registry = new ComponentRegistry([
            'broken' => static fn(): string => 'not a component',
        ]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Component factory must return an instance of Kabuto\Component.');

        $registry->resolve('broken');
    }

    /**
     * Confirms that the renderer resolves and renders a named component synchronously.
     */
    public function testRendererRendersRegisteredComponentByName(): void
    {
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'alert' => RegistryAlertComponent::class,
        ]));

        self::assertSame('<aside data-kind="warning"><h2>Heads up</h2>Check settings</aside>', $renderer->component(
            'alert',
            ['kind' => 'warning'],
            new Slot('Check settings'),
            ['title' => new Slot('Heads up')],
        ));
    }

    /**
     * Confirms that the renderer passes an explicit context to the component.
     */
    public function testRendererPassesProvidedContext(): void
    {
        $renderer = new ComponentRenderer(new ComponentRegistry([
            'context' => RegistryContextComponent::class,
        ]));

        self::assertSame('ja', $renderer->component('context', context: new RenderContext(['locale' => 'ja'])));
    }
}
