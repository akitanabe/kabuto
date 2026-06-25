<?php

declare(strict_types=1);

namespace Kabuto;

use RuntimeException;

abstract class BaseComponent implements Component
{
    /**
     * Stores props and slots shared by class-based components.
     *
     * @param array<string, mixed> $props
     * @param array<string, Slot> $slots
     */
    public function __construct(
        protected array $props = [],
        protected ?Slot $slot = null,
        protected array $slots = [],
        private ?TemplateEngine $templateEngine = null,
    ) {}

    /**
     * Returns a prop value or the provided default when absent.
     */
    protected function prop(string $name, mixed $default = null): mixed
    {
        return $this->props[$name] ?? $default;
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

        return $this->templateEngine->renderFile($path, $data, $context);
    }
}
