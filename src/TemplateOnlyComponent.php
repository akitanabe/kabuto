<?php

declare(strict_types=1);

namespace Kabuto;

use InvalidArgumentException;
use RuntimeException;

final readonly class TemplateOnlyComponent implements Component
{
    /**
     * Stores the fallback template name, props, slots, and engine used to render it.
     *
     * @param array<string, mixed> $props
     * @param array<string, Slot> $slots
     */
    public function __construct(
        private string $name,
        private array $props,
        private ?Slot $slot,
        private array $slots,
        private TemplateEngine $templateEngine,
    ) {}

    /**
     * Renders the same-name template with component props and received slots.
     */
    public function render(RenderContext $context): string
    {
        try {
            return $this->templateEngine->renderFile($this->name, $this->props, $context, $this->slot, $this->slots);
        } catch (TemplateNotFoundException $exception) {
            if ($exception->getMessage() !== 'Template not found: ' . $this->name . '.kbt') {
                throw $exception;
            }

            throw new InvalidArgumentException("Component is not registered: {$this->name}", previous: $exception);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() !== 'TemplateLoader is not configured.') {
                throw $exception;
            }

            throw new InvalidArgumentException("Component is not registered: {$this->name}", previous: $exception);
        }
    }
}
