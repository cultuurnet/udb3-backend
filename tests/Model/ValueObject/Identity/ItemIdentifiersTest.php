<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class ItemIdentifiersTest extends TestCase
{
    private ItemIdentifiers $itemIdentifiers;

    protected function setUp(): void
    {
        $this->itemIdentifiers = new ItemIdentifiers(
            new ItemIdentifier(
                new Url('https://io.uitdatabank.dev/events/3c3f714f-4695-4237-87c5-780d0e599267'),
                '3c3f714f-4695-4237-87c5-780d0e599267',
                ItemType::event()
            ),
            new ItemIdentifier(
                new Url('https://io.uitdatabank.dev/organizer/4ea3d26b-2b91-4dd3-a6c9-e55b1ddb00df'),
                '4ea3d26b-2b91-4dd3-a6c9-e55b1ddb00df',
                ItemType::organizer()
            ),
        );
    }

    /**
     * @test
     */
    public function it_stores_a_list_of_item_identifiers(): void
    {
        $this->assertEquals(
            [
                new ItemIdentifier(
                    new Url('https://io.uitdatabank.dev/events/3c3f714f-4695-4237-87c5-780d0e599267'),
                    '3c3f714f-4695-4237-87c5-780d0e599267',
                    ItemType::event()
                ),
                new ItemIdentifier(
                    new Url('https://io.uitdatabank.dev/organizer/4ea3d26b-2b91-4dd3-a6c9-e55b1ddb00df'),
                    '4ea3d26b-2b91-4dd3-a6c9-e55b1ddb00df',
                    ItemType::organizer()
                ),
            ],
            $this->itemIdentifiers->toArray()
        );
    }
}
