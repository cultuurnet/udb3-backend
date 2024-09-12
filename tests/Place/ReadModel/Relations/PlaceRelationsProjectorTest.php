<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Relations;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Place\Events\OrganizerDeleted;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PlaceRelationsProjectorTest extends TestCase
{
    public const CDBXML_NAMESPACE_32 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

    public const DATE_TIME = '2015-03-01T10:17:19.176169+02:00';

    public const PLACE_ID = 'placeId';
    public const ORGANIZER_ID = 'organizerId';

    /**
     * @var PlaceRelationsRepository&MockObject
     */
    private $repository;

    private PlaceRelationsProjector $projector;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PlaceRelationsRepository::class);

        $this->projector = new PlaceRelationsProjector(
            $this->repository
        );
    }

    /**
     * @test
     */
    public function it_stores_empty_relation_when_place_imported_from_udb2(): void
    {
        $xml = SampleFiles::read(__DIR__ . '/place_imported_from_udb2.cdbxml.xml');

        $placeCreatedFromCdbXml = new PlaceImportedFromUDB2(
            self::PLACE_ID,
            $xml,
            self::CDBXML_NAMESPACE_32
        );

        $domainMessage = $this->createDomainMessage(
            $placeCreatedFromCdbXml->getActorId(),
            $placeCreatedFromCdbXml
        );

        $this->repository
            ->expects($this->once())
            ->method('storeRelations')
            ->with(
                $this->equalTo(self::PLACE_ID),
                $this->equalTo(null)
            );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_removes_relations_when_place_deleted(): void
    {
        $placeDeleted = new PlaceDeleted(self::PLACE_ID);

        $domainMessage = $this->createDomainMessage(
            $placeDeleted->getItemId(),
            $placeDeleted
        );

        $this->repository
            ->expects($this->once())
            ->method('removeRelations')
            ->with(
                $this->equalTo(self::PLACE_ID)
            );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_relation_when_organizer_updated(): void
    {
        $organizerUpdated = new OrganizerUpdated(
            self::PLACE_ID,
            self::ORGANIZER_ID
        );

        $domainMessage = $this->createDomainMessage(
            $organizerUpdated->getItemId(),
            $organizerUpdated
        );

        $this->repository
            ->expects($this->once())
            ->method('storeRelations')
            ->with(
                $this->equalTo(self::PLACE_ID),
                $this->equalTo(self::ORGANIZER_ID)
            );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_clears_relation_when_organizer_deleted(): void
    {
        $organizerDeleted = new OrganizerDeleted(
            self::PLACE_ID,
            self::ORGANIZER_ID
        );

        $domainMessage = $this->createDomainMessage(
            $organizerDeleted->getItemId(),
            $organizerDeleted
        );

        $this->repository
            ->expects($this->once())
            ->method('storeRelations')
            ->with(
                $this->equalTo(self::PLACE_ID),
                null
            );

        $this->projector->handle($domainMessage);
    }

    private function createDomainMessage(string $placeId, Serializable $payload): DomainMessage
    {
        return new DomainMessage(
            $placeId,
            1,
            new Metadata(),
            $payload,
            BroadwayDateTime::fromString(self::DATE_TIME)
        );
    }
}
