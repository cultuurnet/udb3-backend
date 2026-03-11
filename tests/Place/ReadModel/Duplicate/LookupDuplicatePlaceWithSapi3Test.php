<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
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
use CultuurNet\UDB3\Place\Canonical\CanonicalService;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use CultuurNet\UDB3\Search\Results;
use CultuurNet\UDB3\Search\Sapi3SearchService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LookupDuplicatePlaceWithSapi3Test extends TestCase
{
    private LookupDuplicatePlaceWithSapi3 $lookupDuplicatePlaceWithSapi3;

    private Sapi3SearchService&MockObject $sapi3SearchService;

    private DuplicatePlaceRepository&MockObject $duplicatePlaceRepository;

    private CanonicalService&MockObject $canonicalService;

    private IriGeneratorInterface&MockObject $placeIriGenerator;

    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->sapi3SearchService = $this->createMock(Sapi3SearchService::class);
        $this->duplicatePlaceRepository = $this->createMock(DuplicatePlaceRepository::class);
        $this->canonicalService = $this->createMock(CanonicalService::class);
        $this->placeIriGenerator = $this->createMock(IriGeneratorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->lookupDuplicatePlaceWithSapi3 = new LookupDuplicatePlaceWithSapi3(
            $this->sapi3SearchService,
            new UniqueAddressIdentifierFactory(),
            'current-user-id',
            true,
            $this->duplicatePlaceRepository,
            $this->canonicalService,
            $this->placeIriGenerator,
            $this->logger
        );
    }

    public function test_it_returns_null_when_no_results(): void
    {
        $this->sapi3SearchService->expects($this->once())
            ->method('search')
            ->willReturn(new Results(new ItemIdentifiers(), 0));

        $this->assertNull($this->lookupDuplicatePlaceWithSapi3->getDuplicatePlaceUri($this->createPlace()));
    }

    public function test_it_returns_the_place_uri_when_one_result(): void
    {
        $placeId = 'aadcee95-6180-4924-a8eb-ed829d4957a2';
        $query = '(workflowStatus:DRAFT OR workflowStatus:READY_FOR_VALIDATION OR workflowStatus:APPROVED) AND global_address_identifier:online_kerkstraat_1_2000_antwerpen_be';

        $this->sapi3SearchService->expects($this->once())
            ->method('search')
            ->with($query)
            ->willReturn(new Results(new ItemIdentifiers(
                new ItemIdentifier(
                    new Url('https://example.com/place/' . $placeId),
                    $placeId,
                    ItemType::place()
                )
            ), 1));

        $this->assertEquals(
            'https://example.com/place/' . $placeId,
            $this->lookupDuplicatePlaceWithSapi3->getDuplicatePlaceUri($this->createPlace())
        );
    }

    public function test_it_returns_canonical_place_when_multiple_results_and_canonical_found_in_repository(): void
    {
        $place1Id = '21a4c2bc-1aef-4441-bb51-bd6ab9ccd831';
        $place2Id = 'aadcee95-6180-4924-a8eb-ed829d4957a2';
        $canonicalId = '99999999-9999-9999-9999-999999999999';

        $this->sapi3SearchService->method('search')->willReturn(
            new Results(new ItemIdentifiers(
                new ItemIdentifier(new Url('https://example.com/place/' . $place1Id), $place1Id, ItemType::place()),
                new ItemIdentifier(new Url('https://example.com/place/' . $place2Id), $place2Id, ItemType::place()),
            ), 2)
        );

        $this->duplicatePlaceRepository->method('getCanonicalOfPlace')
            ->willReturnMap([
                [$place1Id, null],
                [$place2Id, $canonicalId],
            ]);

        $this->placeIriGenerator->method('iri')
            ->with($canonicalId)
            ->willReturn('https://example.com/place/' . $canonicalId);

        $this->assertEquals(
            'https://example.com/place/' . $canonicalId,
            $this->lookupDuplicatePlaceWithSapi3->getDuplicatePlaceUri($this->createPlace())
        );
    }

    public function test_it_falls_back_to_canonical_service_when_no_canonical_found_in_repository(): void
    {
        $place1Id = '21a4c2bc-1aef-4441-bb51-bd6ab9ccd831';
        $place2Id = 'aadcee95-6180-4924-a8eb-ed829d4957a2';

        $this->sapi3SearchService->method('search')->willReturn(
            new Results(new ItemIdentifiers(
                new ItemIdentifier(new Url('https://example.com/place/' . $place1Id), $place1Id, ItemType::place()),
                new ItemIdentifier(new Url('https://example.com/place/' . $place2Id), $place2Id, ItemType::place()),
            ), 2)
        );

        $this->duplicatePlaceRepository->method('getCanonicalOfPlace')->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Problem with finding a canonical place',
                $this->arrayHasKey('query')
            );

        $this->canonicalService->expects($this->once())
            ->method('getCanonicalFromArrayWithoutThrowing')
            ->with([$place1Id, $place2Id])
            ->willReturn($place1Id);

        $this->placeIriGenerator->method('iri')
            ->with($place1Id)
            ->willReturn('https://example.com/place/' . $place1Id);

        $this->assertEquals(
            'https://example.com/place/' . $place1Id,
            $this->lookupDuplicatePlaceWithSapi3->getDuplicatePlaceUri($this->createPlace())
        );
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
