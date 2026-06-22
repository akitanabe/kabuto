<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\Kabuto;
use PHPUnit\Framework\TestCase;

final class KabutoTest extends TestCase
{
    /**
     * Confirms that the package exposes its current skeleton version.
     */
    public function testVersionReturnsCurrentSkeletonVersion(): void
    {
        self::assertSame('0.1.0', new Kabuto()->version());
    }
}
