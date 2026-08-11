<?php

declare(strict_types=1);

namespace Kabuto\Tests;

use InvalidArgumentException;
use Kabuto\AttributeBag;
use Kabuto\AttributeClassFragments;
use Kabuto\AttributeEntry;
use Kabuto\AttributeProvenance;
use Kabuto\Diagnostics\SourceLocation;
use Kabuto\RenderException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AttributeBagMetadataTest extends TestCase
{
    #[Test]
    public function entriesCanonicalizeNamesAndPreserveMetadataAcrossMerges(): void
    {
        $defaultLocation = new SourceLocation(4, 1, 5);
        $callerLocation = new SourceLocation(21, 2, 8);
        $defaults = AttributeBag::fromEntries([
            new AttributeEntry('CLASS', 'base', AttributeProvenance::Static, $defaultLocation),
            new AttributeEntry('title', 'default', AttributeProvenance::Dynamic, $defaultLocation),
        ]);
        $caller = AttributeBag::fromEntries([
            new AttributeEntry('class', ['caller', 'enabled' => true], AttributeProvenance::Dynamic, $callerLocation),
            new AttributeEntry('TITLE', 'caller', AttributeProvenance::Static, $callerLocation),
        ]);

        $merged = $defaults->merge($caller);
        $class = $merged->entry('class');
        $title = $merged->entry('title');
        self::assertNotNull($class);
        self::assertNotNull($title);
        self::assertInstanceOf(AttributeClassFragments::class, $class->value);
        self::assertSame('caller', $merged->get('title'));
        self::assertSame(AttributeProvenance::Dynamic, $class->provenance);
        self::assertSame($callerLocation, $class->location);
        self::assertSame(AttributeProvenance::Static, $title->provenance);
        self::assertSame($callerLocation, $title->location);

        $arrayMerged = $defaults->merge(['DATA-ID' => '42']);
        $dataId = $arrayMerged->entry('data-id');
        self::assertNotNull($dataId);
        self::assertSame(AttributeProvenance::Static, $dataId->provenance);
        self::assertNull($dataId->location);
    }

    #[Test]
    public function dynamicBagsRequireATargetElementSerializer(): void
    {
        $location = new SourceLocation(7, 1, 8);
        $bag = AttributeBag::fromEntries([
            new AttributeEntry('title', 'A&B', AttributeProvenance::Dynamic, $location),
        ]);

        try {
            $bag->toHtml();
            self::fail('Expected generic serialization to reject dynamic entries.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('target element', $exception->getMessage());
        }

        self::assertSame(' title="A&amp;B"', $bag->toHtmlFor('div'));
        self::assertSame(' href="javascript:x"', new AttributeBag(['href' => 'javascript:x'])->toHtmlFor('a'));
    }

    #[Test]
    public function invalidAndCaseInsensitiveDuplicateNamesAreRejected(): void
    {
        foreach ([['1bad' => 'x'], ['title' => 'a', 'TITLE' => 'b']] as $attributes) {
            try {
                new AttributeBag($attributes);
                self::fail('Expected invalid attribute input to fail.');
            } catch (InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }

        $location = new SourceLocation(9, 2, 4);
        try {
            new AttributeEntry('bad name', 'x', AttributeProvenance::Dynamic, $location);
            self::fail('Expected located invalid attribute input to fail.');
        } catch (RenderException $exception) {
            self::assertSame($location, $exception->location());
        }
    }
}
