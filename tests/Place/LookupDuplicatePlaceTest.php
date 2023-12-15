<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifiers;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Place\MockupHelper\CreateImmutablePlace;
use CultuurNet\UDB3\Place\MockupHelper\CreateLegacyAddress;
use CultuurNet\UDB3\Search\Results;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use PHPUnit\Framework\TestCase;

class LookupDuplicatePlaceTest extends TestCase
{
    /**
     * @dataProvider duplicatePlaceDataProvider
     */
    public function testIsDuplicate(ItemIdentifiers $itemIdentifiers, bool $expectedIsDuplicate, ?string $expectedPlaceId): void
    {
        $sapi3SearchService = $this->createMock(SearchServiceInterface::class);
        $sapi3SearchService->method('search')->willReturn(
            new Results($itemIdentifiers, $itemIdentifiers->count())
        );

        $lookupDuplicatePlace = new LookupDuplicatePlace(
            $sapi3SearchService,
            (new CreateImmutablePlace())->create(),
            (new CreateLegacyAddress())->create()
        );

        $this->assertEquals($expectedIsDuplicate, $lookupDuplicatePlace->isDuplicate());
        $this->assertEquals($expectedPlaceId, $lookupDuplicatePlace->getPlaceId());
    }

    /**
     * Data provider for testIsDuplicate.
     */
    public function duplicatePlaceDataProvider(): array
    {
        return [
            'duplicate place' => [
                new ItemIdentifiers(new ItemIdentifier(
                    new Url('http://example.com/place/1'),
                    'aadcee95-6180-4924-a8eb-ed829d4957a2',
                    ItemType::place()
                )),
                true,
                'aadcee95-6180-4924-a8eb-ed829d4957a2',
            ],
            'non-duplicate place' => [
                new ItemIdentifiers(),
                false,
                null,
            ],
        ];
    }


}
