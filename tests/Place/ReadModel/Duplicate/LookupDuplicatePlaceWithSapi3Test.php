<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Json;
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
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Text\TranslatedTitle;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Results;
use CultuurNet\UDB3\Search\Sapi3SearchService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LookupDuplicatePlaceWithSapi3Test extends TestCase
{
    private LookupDuplicatePlaceWithSapi3 $lookupDuplicatePlaceWithSapi3;

    /** @var Sapi3SearchService & MockObject */
    private $sapi3SearchService;

    protected function setUp(): void
    {
        $this->sapi3SearchService = $this->createMock(Sapi3SearchService::class);

        $documentRepositoryMock = $this->createMock(DocumentRepository::class);

        $documentRepositoryMock->expects($this->atMost(2))
            ->method('fetch')
            ->willReturnCallback(function (string $id) {
                switch ($id) {
                    case '21a4c2bc-1aef-4441-bb51-bd6ab9ccd831':
                    case 'aadcee95-6180-4924-a8eb-ed829d4957a2':
                        return new JsonDocument($id, Json::encode([]));
                    case '55a4c2bc-4441-1aef-1aef-bd6ab9ccd831':
                        return new JsonDocument($id, Json::encode(['duplicatedBy' => 'http://www.example.com/place/21a4c2bc-1aef-4441-bb51-bd6ab9ccd831']));
                    default:
                        return new \Exception('There is no hope');
                }
            });

        $this->lookupDuplicatePlaceWithSapi3 = new LookupDuplicatePlaceWithSapi3(
            $this->sapi3SearchService,
            new UniqueAddressIdentifierFactory(),
            'current-user-id'
        );
    }

    public function test_it_returns_duplicate_place_based_on_search_result(): void
    {
        $itemIdentifiers = new ItemIdentifiers(new ItemIdentifier(
            new Url('http://example.com/place/aadcee95-6180-4924-a8eb-ed829d4957a2'),
            'aadcee95-6180-4924-a8eb-ed829d4957a2',
            ItemType::place()
        ));

        $query = '(workflowStatus:DRAFT OR workflowStatus:READY_FOR_VALIDATION OR workflowStatus:APPROVED) AND unique_address_identifier:online_kerkstraat_1_2000_antwerpen_be_current\-user\-id';

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

    public function test_get_duplicate_place_uri_when_multiple_places_found(): void
    {
        $expectedResult = 'http://www.example.com/place/21a4c2bc-1aef-4441-bb51-bd6ab9ccd831';

        $this->sapi3SearchService->method('search')->willReturnOnConsecutiveCalls(
            new Results(new ItemIdentifiers(
                new ItemIdentifier(
                    new Url('http://www.example.com/place/21a4c2bc-1aef-4441-bb51-bd6ab9ccd831'),
                    '21a4c2bc-1aef-4441-bb51-bd6ab9ccd831',
                    ItemType::place()
                ),
                new ItemIdentifier(
                    new Url('http://www.example.com/place/55a4c2bc-1aef-4441-bb51-bd6ab9ccd123'),
                    '55a4c2bc-1aef-4441-bb51-bd6ab9ccd123',
                    ItemType::place()
                )
            ), 2),
            new Results(new ItemIdentifiers(
                new ItemIdentifier(
                    new Url('http://www.example.com/place/21a4c2bc-1aef-4441-bb51-bd6ab9ccd831'),
                    '21a4c2bc-1aef-4441-bb51-bd6ab9ccd831',
                    ItemType::place()
                )
            ), 1)
        );

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

    public function test_get_duplicate_place_no_canonical_place_found(): void
    {
        $searchResult = new Results(new ItemIdentifiers(new ItemIdentifier(
            new Url('http://www.example.com/place/21a4c2bc-1aef-4441-bb51-bd6ab9ccd831'),
            '21a4c2bc-1aef-4441-bb51-bd6ab9ccd831',
            ItemType::place()
        ), new ItemIdentifier(
            new Url('http://www.example.com/place/aadcee95-6180-4924-a8eb-ed829d4957a2'),
            'aadcee95-6180-4924-a8eb-ed829d4957a2',
            ItemType::place()
        )), 2);

        $this->sapi3SearchService->method('search')->willReturn($searchResult);

        $this->expectException(MultipleDuplicatePlacesFound::class);
        $this->lookupDuplicatePlaceWithSapi3->getDuplicatePlaceUri($this->createPlace());
    }

    private function createPlace(): ImmutablePlace
    {
        return new ImmutablePlace(
            new UUID('aadcee95-6180-4924-a8eb-ed829d4957a2'),
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
