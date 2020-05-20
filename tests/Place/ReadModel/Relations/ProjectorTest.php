<?php

namespace CultuurNet\UDB3\Place\ReadModel\Relations;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Place\Events\OrganizerDeleted;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProjectorTest extends TestCase
{
    const CDBXML_NAMESPACE_32 = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

    const DATE_TIME = '2015-03-01T10:17:19.176169+02:00';
    
    const PLACE_ID = 'placeId';
    const ORGANIZER_ID = 'organizerId';

    /**
     * @var RepositoryInterface|MockObject
     */
    private $repository;

    /**
     * @var Projector
     */
    private $projector;

    protected function setUp()
    {
        $this->repository = $this->createMock(RepositoryInterface::class);

        $this->projector = new Projector(
            $this->repository
        );
    }

    /**
     * @test
     */
    public function it_stores_empty_relation_when_place_imported_from_udb2()
    {
        $xml = file_get_contents(__DIR__ . '/place_imported_from_udb2.cdbxml.xml');
        
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
    public function it_removes_relations_when_place_deleted()
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
    public function it_updates_relation_when_organizer_updated()
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
    public function it_clears_relation_when_organizer_deleted()
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

    /**
     * @param string $placeId
     * @param mixed $payload
     * @return DomainMessage
     */
    private function createDomainMessage($placeId, $payload)
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
