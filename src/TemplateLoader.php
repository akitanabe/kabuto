<?php

declare(strict_types=1);

namespace Kabuto;

final class TemplateLoader
{
    /**
     * Stores the filesystem root used to resolve root-relative template paths.
     */
    public function __construct(
        private readonly string $root,
    ) {}

    /**
     * Loads a regular template file from the configured root.
     */
    public function load(string $path): string
    {
        $path = $this->pathWithDefaultExtension($path);
        $root = realpath($this->root);
        $file = realpath($this->root . DIRECTORY_SEPARATOR . $path);

        if ($root === false || $file === false || !is_file($file) || !$this->isInsideRoot($file, $root)) {
            throw TemplateNotFoundException::forPath($path);
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            throw TemplateNotFoundException::forPath($path);
        }

        return $contents;
    }

    /**
     * Adds the default template extension when the caller did not provide one.
     */
    private function pathWithDefaultExtension(string $path): string
    {
        if (basename($path) === '.' || basename($path) === '..') {
            return $path;
        }

        if (pathinfo($path, PATHINFO_EXTENSION) !== '') {
            return $path;
        }

        return $path . '.kbt';
    }

    /**
     * Checks whether the resolved file path is contained in the resolved root path.
     */
    private function isInsideRoot(string $file, string $root): bool
    {
        return str_starts_with($file, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
    }
}
