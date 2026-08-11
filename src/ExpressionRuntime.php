<?php

declare(strict_types=1);

namespace Kabuto;

final class ExpressionRuntime
{
    public function __construct(
        private FilterRegistry $filters = new FilterRegistry(),
    ) {}

    /**
     * Resolves a variable and applies its explicitly registered filters in order.
     */
    public function evaluate(Expression $expression, RenderScope $scope): mixed
    {
        $value = $scope->get($expression->identifier());

        foreach ($expression->filters() as $index => $name) {
            $filter = $this->filters->get($name);
            if ($filter === null) {
                $message = 'Unknown filter "' . $name . '"';
                $location = $expression->filterLocation($index) ?? $expression->location();

                throw $location === null
                    ? RenderException::at($message, $expression->filterOffset($index))
                    : RenderException::atLocation($message, $location);
            }

            $value = $filter($value);
        }

        return $value;
    }
}
