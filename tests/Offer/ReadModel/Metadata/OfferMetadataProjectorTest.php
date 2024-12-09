<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OfferMetadataProjectorTest extends TestCase
{
    private const OFFER_ID = 'OFFER_ID';
    private const TEST_MAPPING = [
        'udb_api_key' => 'uitdatabank-ui',
        'auth0_client_id' => 'uitdatabank-ui-auth0',
    ];

    /**
     * @var MockObject
     */
    private $repository;

    private OfferMetadataProjector $projector;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(OfferMetadataRepository::class);
        $this->projector = new OfferMetadataProjector(
            $this->repository,
            self::TEST_MAPPING
        );
    }

    /**
     * @dataProvider createdByApiConsumerDataProvider
     */
    public function testItWillProjectOfferMetadataOnEventCreated(
        Metadata $metadata,
        OfferMetadata $expected
    ): void {
        $this->repository
            ->expects($this->once())
            ->method('get')
            ->willReturn(OfferMetadata::default(self::OFFER_ID));

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($expected);

        $this->project($this->createEventCreated(), $metadata);
    }

    /**
     * @dataProvider createdByApiConsumerDataProvider
     */
    public function testItWillProjectOfferMetadataOnPlaceCreated(
        Metadata $metadata,
        OfferMetadata $expected
    ): void {
        $this->repository
            ->expects($this->once())
            ->method('get')
            ->willReturn(OfferMetadata::default(self::OFFER_ID));

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($expected);

        $this->project($this->createPlaceCreated(), $metadata);
    }

    /**
     * @dataProvider createdByApiConsumerDataProvider
     */
    public function testItWillProjectOfferMetadataOnEventCopied(
        Metadata $metadata,
        OfferMetadata $expected
    ): void {
        $this->repository
            ->expects($this->once())
            ->method('get')
            ->willReturn(OfferMetadata::default(self::OFFER_ID));

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($expected);

        $this->project($this->createEventCopied(), $metadata);
    }

    /**
     * @dataProvider createdByApiConsumerDataProvider
     */
    public function testItWillProjectOfferMetadataOnEventImportedFromUdb2(
        Metadata $metadata,
        OfferMetadata $expected
    ): void {
        $this->repository
            ->expects($this->once())
            ->method('get')
            ->willReturn(OfferMetadata::default(self::OFFER_ID));

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($expected);

        $this->project($this->createEventImportedFromUdb2(), $metadata);
    }

    /**
     * @dataProvider createdByApiConsumerDataProvider
     */
    public function testItWillProjectOfferMetadataOnPlaceImportedFromUdb2(
        Metadata $metadata,
        OfferMetadata $expected
    ): void {
        $this->repository
            ->expects($this->once())
            ->method('get')
            ->willReturn(OfferMetadata::default(self::OFFER_ID));

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($expected);

        $this->project($this->createPlaceImportedFromUdb2(), $metadata);
    }

    public function createdByApiConsumerDataProvider(): array
    {
        return [
            'without api key' => [
                new Metadata([]),
                new OfferMetadata(self::OFFER_ID, 'unknown'),
            ],
            'with udb api key' => [
                new Metadata(
                    [
                        'auth_api_key' => 'udb_api_key',
                    ]
                ),
                new OfferMetadata(self::OFFER_ID, 'uitdatabank-ui'),
            ],
            'with auth0 client id' => [
                new Metadata(
                    [
                        'auth_api_client_id' => 'auth0_client_id',
                    ]
                ),
                new OfferMetadata(self::OFFER_ID, 'uitdatabank-ui-auth0'),
            ],
            'with other api key' => [
                new Metadata(
                    [
                        'auth_api_key' => 'other-api-key',
                    ]
                ),
                new OfferMetadata(self::OFFER_ID, 'other'),
            ],
            'with other auth0 client id' => [
                new Metadata(
                    [
                        'auth_api_client_id' => 'other_auth0_client_id-api-key',
                    ]
                ),
                new OfferMetadata(self::OFFER_ID, 'other'),
            ],
        ];
    }

    private function createEventCreated(): EventCreated
    {
        return new EventCreated(
            self::OFFER_ID,
            new Language('en'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            new PermanentCalendar(new OpeningHours())
        );
    }

    private function createPlaceCreated(): PlaceCreated
    {
        return new PlaceCreated(
            self::OFFER_ID,
            new Language('en'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new Address(
                new Street('street'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
            ),
            new PermanentCalendar(new OpeningHours())
        );
    }

    private function createEventCopied(): EventCopied
    {
        return new EventCopied(
            self::OFFER_ID,
            'original_event_id',
            new Calendar(CalendarType::permanent())
        );
    }

    private function createEventImportedFromUdb2(): EventImportedFromUDB2
    {
        return new EventImportedFromUDB2(
            self::OFFER_ID,
            'CDB_XML',
            'NAMESPACE_URI'
        );
    }

    private function createPlaceImportedFromUdb2(): PlaceImportedFromUDB2
    {
        return new PlaceImportedFromUDB2(
            self::OFFER_ID,
            'CDB_XML',
            'NAMESPACE_URI'
        );
    }

    protected function project(
        Serializable $event,
        Metadata $metadata
    ): void {
        $this->projector->handle(
            new DomainMessage(
                self::OFFER_ID,
                1,
                $metadata,
                $event,
                DateTime::now()
            )
        );
    }
}
