<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\Kabuto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class KabutoTest extends TestCase
{
    /**
     * Confirms that the package exposes its current skeleton version.
     */
    #[Test]
    public function versionReturnsCurrentSkeletonVersion(): void
    {
        self::assertSame('0.1.0', new Kabuto()->version());
    }
}
