<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

class LocationIdTest extends TestCase
{
    /**
     * @test
     */
    public function it_requires_a_non_empty_string_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('LocationId can\'t have an empty value.');

        new LocationId('');
    }

    /**
     * @dataProvider locationDataProvider
     * @test
     */
    public function it_recognizes_nil_locations(LocationId $locationId, bool $isOnlineLocation): void
    {
        $this->assertEquals($isOnlineLocation, $locationId->isNilLocation());
    }

    public function locationDataProvider(): array
    {
        return [
            [
                new LocationId('https://io.uitdatabank.dev/places/00000000-0000-0000-0000-000000000000'),
                true,
            ],
            [
                new LocationId('https://io.uitdatabank.dev/places/00000000-0000-0000-0000-00000000000'),
                false,
            ],
            [
                new LocationId('https://io.uitdatabank.dev/places/00000000-0000-0000-0000-0000000000001'),
                false,
            ],
            [
                new LocationId('https://io.uitdatabank.dev/places/df91e7b3-bdf1-4ca9-8cb2-9239fe14a7e1'),
                false,
            ],
            [
                new LocationId(UUID::NIL),
                true,
            ],
            [
                new LocationId('82cf263f-f9ae-4d25-bdd4-75ce29c2a6af'),
                false,
            ],
        ];
    }
}
