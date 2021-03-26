<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OfferMetadataProjectorTest extends TestCase
{
    private const OFFER_ID = 'OFFER_ID';
    private const TEST_MAPPING = [
        'udb_api_key' => 'uitdatabank-ui',
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
                        'auth_api_key' => 'udb_api_key'
                    ]
                ),
                new OfferMetadata(self::OFFER_ID, 'uitdatabank-ui'),
            ],
            'with other api key' => [
                new Metadata(
                    [
                        'auth_api_key' => 'other-api-key',
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
