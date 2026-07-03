<?php

declare(strict_types=1);

namespace Kabuto;

use ReflectionClass;
use ReflectionProperty;
use RuntimeException;

abstract class BaseComponent implements Component
{
    /**
     * Stores props and slots shared by class-based components.
     *
     * @param array<string, mixed> $props
     * @param array<string, Slot> $slots
     * @param array<string, mixed>|AttributeBag $attributes
     */
    public function __construct(
        protected array $props = [],
        protected ?Slot $slot = null,
        protected array $slots = [],
        private ?TemplateEngine $templateEngine = null,
        private array|AttributeBag $attributes = [],
    ) {}

    /**
     * Returns public non-static properties accepted as dynamic props.
     *
     * @return list<string>
     */
    public static function acceptsProps(): array
    {
        $properties = [];
        $class = new ReflectionClass(static::class);

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $properties[] = $property->getName();
        }

        return $properties;
    }

    /**
     * Returns a prop value or the provided default when absent.
     */
    protected function prop(string $name, mixed $default = null): mixed
    {
        return $this->props[$name] ?? $default;
    }

    /**
     * Returns a normal attribute value or the provided default when absent.
     */
    protected function attribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes()->get($name, $default);
    }

    /**
     * Returns normal component attributes separately from props.
     */
    protected function attributes(): AttributeBag
    {
        if ($this->attributes instanceof AttributeBag) {
            return $this->attributes;
        }

        return new AttributeBag($this->attributes);
    }

    /**
     * Returns the default slot or a named slot when requested.
     */
    protected function slot(?string $name = null): ?Slot
    {
        if ($name === null) {
            return $this->slot;
        }

        return $this->slots[$name] ?? null;
    }

    /**
     * Renders a component-owned template file with the current engine.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $path, array $data = [], ?RenderContext $context = null): string
    {
        if ($this->templateEngine === null) {
            throw new RuntimeException('TemplateEngine is not configured for component views.');
        }

        return $this->templateEngine->renderFile($path, $data, $context, $this->slot, $this->slots);
    }
}
