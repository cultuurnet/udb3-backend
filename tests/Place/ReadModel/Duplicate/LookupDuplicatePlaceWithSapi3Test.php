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
use CultuurNet\UDB3\Search\Results;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LookupDuplicatePlaceWithSapi3Test extends TestCase
{
    private LookupDuplicatePlaceWithSapi3 $lookupDuplicatePlaceWithSapi3;

    /** @var SearchServiceInterface & MockObject  */
    private $sapi3SearchService;

    protected function setUp(): void
    {
        $this->sapi3SearchService = $this->createMock(SearchServiceInterface::class);

        $this->lookupDuplicatePlaceWithSapi3 = new LookupDuplicatePlaceWithSapi3(
            $this->sapi3SearchService,
            'current-user-id'
        );
    }

    /**
     * @test
     */
    public function it_returns_duplicate_place_based_on_search_result(): void
    {
        $place = $this->createPlace();

        $itemIdentifiers = new ItemIdentifiers(new ItemIdentifier(
            new Url('http://example.com/place/1'),
            'aadcee95-6180-4924-a8eb-ed829d4957a2',
            ItemType::place()
        ));

        $this->sapi3SearchService->expects($this->once())
            ->method('search')
            ->with('unique_address_identifier:online_tesstraat 1_2000_antwerpen_be_current-user-id', 1)
            ->willReturn(
                new Results($itemIdentifiers, $itemIdentifiers->count())
            );

        $duplicatePlaceId = $this->lookupDuplicatePlaceWithSapi3->getDuplicatePlaceId($place);

        $this->assertEquals('aadcee95-6180-4924-a8eb-ed829d4957a2', $duplicatePlaceId);
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
                new Udb3Street('Tesstraat 1'),
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
