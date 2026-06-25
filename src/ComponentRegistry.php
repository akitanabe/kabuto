<?php

declare(strict_types=1);

namespace Kabuto;

use InvalidArgumentException;
use UnexpectedValueException;

final class ComponentRegistry
{
    /**
     * Stores explicit component definitions keyed by template component name.
     *
     * @param array<string, mixed> $definitions
     */
    public function __construct(
        private array $definitions = [],
    ) {}

    /**
     * Resolves a registered component name into a component instance.
     *
     * @param array<string, mixed> $props
     * @param array<string, Slot> $slots
     */
    public function resolve(
        string $name,
        array $props = [],
        ?Slot $slot = null,
        array $slots = [],
        ?TemplateEngine $templateEngine = null,
    ): Component {
        if (!array_key_exists($name, $this->definitions)) {
            throw new InvalidArgumentException("Component is not registered: {$name}");
        }

        $definition = $this->definitions[$name];

        if (is_string($definition)) {
            if (!class_exists($definition) || !is_subclass_of($definition, Component::class)) {
                throw new InvalidArgumentException(
                    "Registered component class must implement Kabuto\\Component: {$definition}",
                );
            }

            return new $definition($props, $slot, $slots, $templateEngine);
        }

        if (!is_callable($definition)) {
            throw new InvalidArgumentException("Registered component definition is not callable: {$name}");
        }

        $component = $definition($props, $slot, $slots, $templateEngine);

        if (!$component instanceof Component) {
            throw new UnexpectedValueException('Component factory must return an instance of Kabuto\Component.');
        }

        return $component;
    }
}
