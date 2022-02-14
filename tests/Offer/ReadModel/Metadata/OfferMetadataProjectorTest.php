<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

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

    /**
     * @var OfferMetadataProjector
     */
    private $projector;

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

    /**
     * @return array
     */
    public function createdByApiConsumerDataProvider()
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
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            new Calendar(CalendarType::PERMANENT())
        );
    }

    private function createPlaceCreated(): PlaceCreated
    {
        return new PlaceCreated(
            self::OFFER_ID,
            new Language('en'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new Address(
                new Street('street'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );
    }

    private function createEventCopied(): EventCopied
    {
        return new EventCopied(
            self::OFFER_ID,
            'original_event_id',
            new Calendar(CalendarType::PERMANENT())
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
        $event,
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
