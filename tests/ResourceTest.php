<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\Resource;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResourceTest extends TestCase
{
    /**
     * Confirms that a resource loads once and returns the cached value afterwards.
     */
    #[Test]
    public function readLoadsOnceAndReturnsCachedValue(): void
    {
        $loadCount = 0;
        $resource = new Resource(static function () use (&$loadCount): string {
            $loadCount++;

            return 'value-' . $loadCount;
        });

        self::assertSame('value-1', $resource->read());
        self::assertSame('value-1', $resource->read());
        self::assertSame(1, $loadCount);
    }

    /**
     * Confirms that null is treated as a cached resource value.
     */
    #[Test]
    public function readCachesNullValue(): void
    {
        $loadCount = 0;
        $resource = new Resource(static function () use (&$loadCount): null {
            $loadCount++;

            return null;
        });

        self::assertNull($resource->read());
        self::assertNull($resource->read());
        self::assertSame(1, $loadCount);
    }
}
