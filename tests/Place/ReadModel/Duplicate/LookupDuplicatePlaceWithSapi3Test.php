<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\Place\ImmutablePlace;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address as Udb3Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality as Udb3Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode as Udb3PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street as Udb3Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifiers;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use CultuurNet\UDB3\Search\Results;
use CultuurNet\UDB3\Search\Sapi3SearchService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LookupDuplicatePlaceWithSapi3Test extends TestCase
{
    private LookupDuplicatePlaceWithSapi3 $lookupDuplicatePlaceWithSapi3;

    private Sapi3SearchService&MockObject $sapi3SearchService;

    private DuplicatePlaceRepository&MockObject $duplicatePlaceRepository;

    protected function setUp(): void
    {
        $this->sapi3SearchService = $this->createMock(Sapi3SearchService::class);

        $this->duplicatePlaceRepository = $this->createMock(DuplicatePlaceRepository::class);

        $this->lookupDuplicatePlaceWithSapi3 = new LookupDuplicatePlaceWithSapi3(
            $this->sapi3SearchService,
            new UniqueAddressIdentifierFactory(),
            'current-user-id',
            true,
            $this->duplicatePlaceRepository
        );
    }

    public function test_it_returns_duplicate_place_based_on_search_result(): void
    {
        $itemIdentifiers = new ItemIdentifiers(new ItemIdentifier(
            new Url('http://example.com/place/aadcee95-6180-4924-a8eb-ed829d4957a2'),
            'aadcee95-6180-4924-a8eb-ed829d4957a2',
            ItemType::place()
        ));

        $query = '(workflowStatus:DRAFT OR workflowStatus:READY_FOR_VALIDATION OR workflowStatus:APPROVED) AND global_address_identifier:online_kerkstraat_1_2000_antwerpen_be';

        $this->sapi3SearchService->expects($this->once())
            ->method('search')
            ->with($query)
            ->willReturn(
                new Results($itemIdentifiers, $itemIdentifiers->count())
            );

        $duplicatePlaceUri = $this->lookupDuplicatePlaceWithSapi3->getDuplicatePlaceUri($this->createPlace());

        $this->assertEquals('http://example.com/place/aadcee95-6180-4924-a8eb-ed829d4957a2', $duplicatePlaceUri);
    }

    /**
     * @dataProvider searchResultsProvider
     */
    public function test_get_duplicate_place_uri(Results $searchResult, ?string $expectedResult): void
    {
        $this->sapi3SearchService->method('search')->willReturn($searchResult);

        $duplicatePlaceUri = $this->lookupDuplicatePlaceWithSapi3->getDuplicatePlaceUri($this->createPlace());
        $this->assertEquals($expectedResult, $duplicatePlaceUri);
    }

    public function searchResultsProvider(): array
    {
        return [
            'No results' => [new Results(new ItemIdentifiers(), 0), null],
            'One result' => [
                new Results(new ItemIdentifiers(new ItemIdentifier(
                    new Url('http://www.example.com/place/21a4c2bc-1aef-4441-bb51-bd6ab9ccd831'),
                    '21a4c2bc-1aef-4441-bb51-bd6ab9ccd831',
                    ItemType::place()
                )), 1),
                'http://www.example.com/place/21a4c2bc-1aef-4441-bb51-bd6ab9ccd831',
            ],
        ];
    }

    public function test_it_returns_first_item_with_canonical_when_no_non_duplicate_results_found(): void
    {
        $place1Id = '21a4c2bc-1aef-4441-bb51-bd6ab9ccd831';
        $place2Id = 'aadcee95-6180-4924-a8eb-ed829d4957a2';

        $this->sapi3SearchService->method('search')->willReturn(
            new Results(new ItemIdentifiers(
                new ItemIdentifier(
                    new Url('https://www.example.com/place/' . $place1Id),
                    $place1Id,
                    ItemType::place()
                ),
                new ItemIdentifier(
                    new Url('https://www.example.com/place/' . $place2Id),
                    $place2Id,
                    ItemType::place()
                )
            ), 2)
        );

        $this->duplicatePlaceRepository->expects($this->atLeastOnce())
            ->method('getCanonicalOfPlace')
            ->willReturnMap([
                [$place1Id, null],
                [$place2Id, '99999999-9999-9999-9999-999999999999'],
            ]);

        $duplicatePlaceUri = $this->lookupDuplicatePlaceWithSapi3->getDuplicatePlaceUri($this->createPlace());

        $this->assertEquals('https://www.example.com/place/99999999-9999-9999-9999-999999999999', $duplicatePlaceUri);
    }

    public function test_it_throws_when_no_canonical_found_and_no_non_duplicate_results(): void
    {
        $place1Id = '21a4c2bc-1aef-4441-bb51-bd6ab9ccd831';
        $place2Id = 'aadcee95-6180-4924-a8eb-ed829d4957a2';

        $this->sapi3SearchService->method('search')->willReturn(
            new Results(new ItemIdentifiers(
                new ItemIdentifier(new Url('https://www.example.com/place/' . $place1Id), $place1Id, ItemType::place()),
                new ItemIdentifier(new Url('https://www.example.com/place/' . $place2Id), $place2Id, ItemType::place()),
            ), 2)
        );

        $this->duplicatePlaceRepository->method('getCanonicalOfPlace')->willReturn(null);

        $this->expectException(MultipleDuplicatePlacesFound::class);
        $this->lookupDuplicatePlaceWithSapi3->getDuplicatePlaceUri($this->createPlace());
    }

    private function createPlace(): ImmutablePlace
    {
        return new ImmutablePlace(
            new Uuid('aadcee95-6180-4924-a8eb-ed829d4957a2'),
            new Language('nl'),
            new TranslatedTitle(
                new Language('nl'),
                new Title('Online')
            ),
            new PermanentCalendar(new OpeningHours()),
            new TranslatedAddress(new Language('nl'), new Udb3Address(
                new Udb3Street('Kerkstraat 1'),
                new Udb3PostalCode('2000'),
                new Udb3Locality('Antwerpen'),
                new CountryCode('BE')
            )),
            new Categories(
                new Category(
                    new CategoryID('0.6.0.0.0'),
                    new CategoryLabel('Beurs'),
                    new CategoryDomain('eventtype')
                )
            )
        );
    }
}
