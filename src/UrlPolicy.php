<?php

declare(strict_types=1);

namespace Kabuto;

use Kabuto\Diagnostics\SourceLocation;

final class UrlPolicy
{
    public function validate(string $value, SourceLocation $location): void
    {
        if (
            str_contains($value, '\\')
            || str_starts_with($value, '//')
            || preg_match('/[\x00-\x20\x7F]/', $value) === 1
            || !$this->hasAllowedForm($value)
        ) {
            throw RenderException::atLocation('Invalid URL for dynamic attribute', $location);
        }
    }

    private function hasAllowedForm(string $value): bool
    {
        if (str_starts_with($value, '/') || str_starts_with($value, '?') || str_starts_with($value, '#')) {
            return true;
        }

        if (preg_match('/^([A-Za-z][A-Za-z0-9+.-]*):/', $value, $matches) !== 1) {
            $firstSegmentLength = strcspn(string: $value, characters: '/?#');

            return !str_contains(substr($value, offset: 0, length: $firstSegmentLength), ':');
        }

        return in_array(strtolower($matches[1]), ['http', 'https', 'mailto', 'tel'], strict: true);
    }
}
