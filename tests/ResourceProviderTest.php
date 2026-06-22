<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use Kabuto\Provider;
use Kabuto\RenderContext;
use Kabuto\Resource;
use Kabuto\Slot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResourceProviderTest extends TestCase
{
    /**
     * Confirms that a resource stored in render context can be read externally.
     */
    #[Test]
    public function renderContextStoresReadableResource(): void
    {
        $resource = new Resource(static fn(): string => 'profile-ready');
        $context = new RenderContext(['profile' => $resource]);
        $provided = $context->get('profile');

        self::assertInstanceOf(Resource::class, $provided);
        self::assertSame('profile-ready', $provided->read());
    }

    /**
     * Confirms that a provider resource is readable by a child slot and remains scoped.
     */
    #[Test]
    public function providerResourceCanBeReadByChildSlotWithoutLeakingContext(): void
    {
        $innerLoadCount = 0;
        $outerLoadCount = 0;
        $outerResource = new Resource(static function () use (&$outerLoadCount): string {
            $outerLoadCount++;

            return 'outer';
        });
        $innerResource = new Resource(static function () use (&$innerLoadCount): string {
            $innerLoadCount++;

            return 'inner';
        });
        $context = new RenderContext(['data' => $outerResource]);
        $provider = new Provider(
            props: ['name' => 'data', 'value' => $innerResource],
            slot: new Slot(static function (RenderContext $context): string {
                $resource = $context->get('data');

                if (!$resource instanceof Resource) {
                    self::fail('Expected provider value to be a resource.');
                }

                $first = $resource->read();
                $second = $resource->read();

                if (!is_string($first) || !is_string($second)) {
                    self::fail('Expected resource reads to return strings.');
                }

                return $first . ':' . $second;
            }),
        );

        self::assertSame('inner:inner', $provider->render($context));
        self::assertSame(1, $innerLoadCount);
        self::assertSame($outerResource, $context->get('data'));
        self::assertSame(0, $outerLoadCount);
    }
}
