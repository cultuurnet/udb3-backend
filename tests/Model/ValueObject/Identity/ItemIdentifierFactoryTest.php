<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class ItemIdentifierFactoryTest extends TestCase
{
    private ItemIdentifierFactory $itemIdentifierFactory;

    protected function setUp(): void
    {
        $this->itemIdentifierFactory = new ItemIdentifierFactory(
            'https?://.+\.uitdatabank\.dev/(?<itemType>[event|place|organizer]+)s?/(?<itemId>[a-zA-Z0-9\-]+)'
        );
    }

    /**
     * @test
     * @dataProvider itemIdentifierDataProvider
     */
    public function it_generates_item_identifier_from_a_url(Url $url, ItemIdentifier $itemIdentifier): void
    {
        $this->assertEquals(
            $itemIdentifier,
            $this->itemIdentifierFactory->fromUrl($url)
        );
    }

    public function itemIdentifierDataProvider(): array
    {
        return [
            'An event with event path' => [
                new Url('https://io.uitdatabank.dev/event/3c3f714f-4695-4237-87c5-780d0e599267'),
                new ItemIdentifier(
                    new Url('https://io.uitdatabank.dev/event/3c3f714f-4695-4237-87c5-780d0e599267'),
                    '3c3f714f-4695-4237-87c5-780d0e599267',
                    ItemType::event()
                ),
            ],
            'An event with plural path' => [
                new Url('https://io.uitdatabank.dev/events/3c3f714f-4695-4237-87c5-780d0e599267'),
                new ItemIdentifier(
                    new Url('https://io.uitdatabank.dev/events/3c3f714f-4695-4237-87c5-780d0e599267'),
                    '3c3f714f-4695-4237-87c5-780d0e599267',
                    ItemType::event()
                ),
            ],
            'A place' => [
                new Url('https://io.uitdatabank.dev/place/3c3f714f-4695-4237-87c5-780d0e599267'),
                new ItemIdentifier(
                    new Url('https://io.uitdatabank.dev/place/3c3f714f-4695-4237-87c5-780d0e599267'),
                    '3c3f714f-4695-4237-87c5-780d0e599267',
                    ItemType::place()
                ),
            ],
            'A place with plural path' => [
                new Url('https://io.uitdatabank.dev/places/3c3f714f-4695-4237-87c5-780d0e599267'),
                new ItemIdentifier(
                    new Url('https://io.uitdatabank.dev/places/3c3f714f-4695-4237-87c5-780d0e599267'),
                    '3c3f714f-4695-4237-87c5-780d0e599267',
                    ItemType::place()
                ),
            ],
            'An organizer' => [
                new Url('https://io.uitdatabank.dev/organizer/3c3f714f-4695-4237-87c5-780d0e599267'),
                new ItemIdentifier(
                    new Url('https://io.uitdatabank.dev/organizer/3c3f714f-4695-4237-87c5-780d0e599267'),
                    '3c3f714f-4695-4237-87c5-780d0e599267',
                    ItemType::organizer()
                ),
            ],
            'An organizer with plural path' => [
                new Url('https://io.uitdatabank.dev/organizer/3c3f714f-4695-4237-87c5-780d0e599267'),
                new ItemIdentifier(
                    new Url('https://io.uitdatabank.dev/organizer/3c3f714f-4695-4237-87c5-780d0e599267'),
                    '3c3f714f-4695-4237-87c5-780d0e599267',
                    ItemType::organizer()
                ),
            ],
        ];
    }
}
