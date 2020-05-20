<?php

namespace CultuurNet\UDB3\Place\ReadModel\JSONLD;

use Broadway\Domain\DateTime;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Event\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\ReadModel\Relations\RepositoryInterface;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\RecordedOn;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RelatedPlaceLDProjectorTest extends TestCase
{
    /**
     * @var \CultuurNet\UDB3\Place\ReadModel\JSONLD\RelatedPlaceLDProjector
     */
    protected $projector;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    protected $documentRepository;

    /**
     * @var EntityServiceInterface
     */
    private $organizerService;

    /**
     * @var RepositoryInterface|MockObject
     */
    private $placeRelations;

    /**
     * @var DomainMessageBuilder
     */
    private $domainMessageBuilder;

    /**
     * @var \CultuurNet\UDB3\RecordedOn
     */
    protected $recordedOn;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->documentRepository = new InMemoryDocumentRepository();
        $this->placeRelations = $this->createMock(RepositoryInterface::class);
        $this->organizerService = $this->createMock(EntityServiceInterface::class);

        $this->projector = new RelatedPlaceLDProjector(
            $this->documentRepository,
            $this->organizerService,
            $this->placeRelations
        );

        $this->domainMessageBuilder = new DomainMessageBuilder();

        $this->recordedOn = RecordedOn::fromBroadwayDateTime(
            DateTime::fromString('2018-01-01T08:30:00+0100')
        );
    }

    /**
     * @test
     */
    public function it_updates_all_related_places_when_an_organizer_is_updated()
    {
        $kantoorLeuvenId = 'c01f5799-b914-487d-9e00-6c224ab6555e';
        $kantoorKesselLoId = '57eaaa61-31d8-42c3-8d1b-1b1ecbb153a8';

        $stadLeuvenId = 'dbef3da9-13f0-42be-9ac2-8593376a508a';

        $stadLeuvenJSONLD = json_encode(
            [
                'name' => [
                    'nl' => 'Stad Leuven',
                ],
                'email' => [
                    'info@leuven.be',
                ],
            ]
        );

        $kantoorLeuvenJSONLD = json_encode(
            [
                'name' => [
                    'nl' => 'Kantoor Leuven',
                ],
                'modified' => $this->recordedOn->toString(),
            ]
        );
        $initialKantoorLeuvenDocument = new JsonDocument(
            $kantoorLeuvenId,
            $kantoorLeuvenJSONLD
        );
        $this->documentRepository->save($initialKantoorLeuvenDocument);

        $kantoorKesselLoJSONLD = json_encode(
            [
                'name' => [
                    'nl' => 'Kantoor Kessel-Lo',
                ],
                'modified' => $this->recordedOn->toString(),
            ]
        );
        $initialKantoorKesselLoDocument = new JsonDocument(
            $kantoorKesselLoId,
            $kantoorKesselLoJSONLD
        );
        $this->documentRepository->save($initialKantoorKesselLoDocument);

        $this->placeRelations
            ->expects($this->once())
            ->method('getPlacesOrganizedByOrganizer')
            ->with($stadLeuvenId)
            ->willReturn(
                [
                    $kantoorLeuvenId,
                    $kantoorKesselLoId,
                ]
            );

        $this->organizerService
            ->expects($this->once())
            ->method('getEntity')
            ->with($stadLeuvenId)
            ->willReturn($stadLeuvenJSONLD);

        $organizerProjectedToJSONLD = new OrganizerProjectedToJSONLD(
            $stadLeuvenId,
            'organizers/' . $stadLeuvenId
        );

        $this->projector->handle(
            $this->domainMessageBuilder->create($organizerProjectedToJSONLD)
        );

        $expectedKantoorLeuvenBody = (object) [
            'name' => (object) [
                'nl' => 'Kantoor Leuven',
            ],
            'organizer' => (object) [
                'name' => (object) [
                    'nl' => 'Stad Leuven',
                ],
                'email' => [
                    'info@leuven.be',
                ],
            ],
            'modified' => $this->recordedOn->toString(),

        ];

        $expectedKantoorKesselLoBody = (object) [
            'name' => (object) [
                'nl' => 'Kantoor Kessel-Lo',
            ],
            'organizer' => (object) [
                'name' => (object) [
                    'nl' => 'Stad Leuven',
                ],
                'email' => [
                    'info@leuven.be',
                ],
            ],
            'modified' => $this->recordedOn->toString(),
        ];

        $this->assertEquals(
            $expectedKantoorLeuvenBody,
            $this->documentRepository->get($kantoorLeuvenId)->getBody()
        );

        $this->assertEquals(
            $expectedKantoorKesselLoBody,
            $this->documentRepository->get($kantoorKesselLoId)->getBody()
        );
    }
}
