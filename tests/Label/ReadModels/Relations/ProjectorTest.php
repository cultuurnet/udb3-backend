<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\LabelAdded as LabelAddedToEvent;
use CultuurNet\UDB3\Event\Events\LabelRemoved as LabelRemovedFromEvent;
use CultuurNet\UDB3\Event\Events\LabelsImported as EventLabelsImported;
use CultuurNet\UDB3\Label\LabelEventRelationTypeResolver;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface as RelationsReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved;
use CultuurNet\UDB3\Offer\Events\AbstractLabelsImported;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\Events\LabelAdded as LabelAddedToOrganizer;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved as LabelRemovedFromOrganizer;
use CultuurNet\UDB3\Organizer\Events\LabelsImported as OrganizerLabelsImported;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\LabelAdded as LabelAddedToPlace;
use CultuurNet\UDB3\Place\Events\LabelRemoved as LabelRemovedFromPlace;
use CultuurNet\UDB3\Place\Events\LabelsImported as PlaceLabelsImported;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProjectorTest extends TestCase
{
    private string $labelName;

    private string $relationId;

    private WriteRepositoryInterface&MockObject $writeRepository;

    private RelationsReadRepositoryInterface&MockObject $relationsReadRepository;

    private Projector $projector;

    protected function setUp(): void
    {
        $this->labelName = 'labelName';

        $this->relationId = $this->getRelationId();

        $this->writeRepository = $this->createMock(WriteRepositoryInterface::class);
        $this->relationsReadRepository = $this->createMock(RelationsReadRepositoryInterface::class);
        $offerTypeResolver = new LabelEventRelationTypeResolver();

        $this->projector = new Projector(
            $this->writeRepository,
            $this->relationsReadRepository,
            $offerTypeResolver
        );
    }

    /**
     * @test
     * @dataProvider labelAddedEventDataProvider
     *
     * @param AbstractLabelAdded|LabelAdded $labelAdded
     */
    public function it_handles_label_added_events(
        string $relationId,
        $labelAdded,
        RelationType $relationType
    ): void {
        $domainMessage = $this->createDomainMessage(
            $relationId,
            $labelAdded
        );

        $this->writeRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->labelName,
                $relationType,
                $this->relationId
            );

        $this->projector->handle($domainMessage);
    }

    public function labelAddedEventDataProvider(): array
    {
        return [
            [
                $this->getRelationId(),
                new LabelAddedToEvent(
                    $this->getRelationId(),
                    'labelName'
                ),
                RelationType::event(),
            ],
            [
                $this->getRelationId(),
                new LabelAddedToPlace(
                    $this->getRelationId(),
                    'labelName'
                ),
                RelationType::place(),
            ],
            [
                $this->getRelationId(),
                new LabelAddedToOrganizer(
                    $this->getRelationId(),
                    'labelName'
                ),
                RelationType::organizer(),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider labelRemovedEventDataProvider
     *
     * @param AbstractLabelRemoved|LabelRemovedFromOrganizer $labelRemoved
     */
    public function it_handles_label_deleted_events(
        string $relationId,
        $labelRemoved
    ): void {
        $domainMessage = $this->createDomainMessage(
            $relationId,
            $labelRemoved
        );

        $this->writeRepository->expects($this->once())
            ->method('deleteByLabelNameAndRelationId')
            ->with($this->labelName, $relationId);

        $this->projector->handle($domainMessage);
    }

    public function labelRemovedEventDataProvider(): array
    {
        return [
            [
                $this->getRelationId(),
                new LabelRemovedFromEvent(
                    $this->getRelationId(),
                    'labelName'
                ),
            ],
            [
                $this->getRelationId(),
                new LabelRemovedFromPlace(
                    $this->getRelationId(),
                    'labelName'
                ),
            ],
            [
                $this->getRelationId(),
                new LabelRemovedFromOrganizer(
                    $this->getRelationId(),
                    'labelName'
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider labelsImportedDataProvider
     *
     * @param AbstractLabelsImported|ImportLabels $labelsImported
     */
    public function it_handles_import_labels_events(
        string $relationId,
        RelationType $relationType,
        $labelsImported
    ): void {
        $domainMessage = $this->createDomainMessage(
            $relationId,
            $labelsImported
        );

        $this->writeRepository->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                [
                    'foo',
                    $relationType,
                    $relationId,
                    true,
                ],
                [
                    'bar',
                    $relationType,
                    $relationId,
                    true,
                ]
            );

        $this->projector->handle($domainMessage);
    }

    public function labelsImportedDataProvider(): array
    {
        return [
            [
                $this->getRelationId(),
                RelationType::event(),
                new EventLabelsImported(
                    $this->getRelationId(),
                    ['foo'],
                    ['bar']
                ),
            ],
            [
                $this->getRelationId(),
                RelationType::place(),
                new PlaceLabelsImported(
                    $this->getRelationId(),
                    ['foo'],
                    ['bar']
                ),
            ],
            [
                $this->getRelationId(),
                RelationType::organizer(),
                new OrganizerLabelsImported(
                    $this->getRelationId(),
                    ['foo'],
                    ['bar']
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider fromUdb2DataProvider
     */
    public function it_handles_import_and_update_events_from_udb2(
        string $itemId,
        Serializable $payload,
        RelationType $relationType
    ): void {
        $domainMessage = $this->createDomainMessage(
            $itemId,
            $payload
        );

        $this->writeRepository->expects($this->once())
            ->method('deleteImportedByRelationId')
            ->with($itemId);

        $this->writeRepository->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                [
                    '2dotstwice',
                    $relationType,
                    $itemId,
                    true,
                ],
                [
                    'cultuurnet',
                    $relationType,
                    $itemId,
                    true,
                ],
            );


        $this->relationsReadRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with($itemId)
            ->willReturn([]);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_only_add_labels_from_udb2_when_updating_with_labels_already_present_in_udb3(): void
    {
        $itemId = 'd53c2bc9-8f0e-4c9a-8457-77e8b3cab3d1';
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        $domainMessage = $this->createDomainMessage(
            $itemId,
            new OrganizerUpdatedFromUDB2(
                $itemId,
                SampleFiles::read(__DIR__ . '/Samples/organizer_with_same_label_but_different_casing.xml'),
                $cdbXmlNamespaceUri
            )
        );

        // Make sure to have different casing for the UDB3 label then the UDB2 label.
        $this->relationsReadRepository->expects($this->once())
            ->method('getLabelRelationsForItem')
            ->with($itemId)
            ->willReturn([
                new LabelRelation(
                    '2DOTStwice',
                    RelationType::organizer(),
                    '123',
                    false
                ),
            ]);

        $this->writeRepository->expects($this->once())
            ->method('save')
            ->with(
                'cultuurnet',
                RelationType::organizer(),
                $itemId,
                true
            );

        $this->projector->handle($domainMessage);
    }

    public function fromUdb2DataProvider(): array
    {
        $itemId = 'd53c2bc9-8f0e-4c9a-8457-77e8b3cab3d1';
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3');

        return [
            [
                $itemId,
                new EventImportedFromUDB2(
                    $itemId,
                    SampleFiles::read(__DIR__ . '/Samples/event.xml'),
                    $cdbXmlNamespaceUri
                ),
                RelationType::event(),
            ],
            [
                $itemId,
                new PlaceImportedFromUDB2(
                    $itemId,
                    SampleFiles::read(__DIR__ . '/Samples/place.xml'),
                    $cdbXmlNamespaceUri
                ),
                RelationType::place(),
            ],
            [
                $itemId,
                new OrganizerImportedFromUDB2(
                    $itemId,
                    SampleFiles::read(__DIR__ . '/Samples/organizer.xml'),
                    $cdbXmlNamespaceUri
                ),
                RelationType::organizer(),
            ],
            [
                $itemId,
                new EventUpdatedFromUDB2(
                    $itemId,
                    SampleFiles::read(__DIR__ . '/Samples/event.xml'),
                    $cdbXmlNamespaceUri
                ),
                RelationType::event(),
            ],
            [
                $itemId,
                new PlaceUpdatedFromUDB2(
                    $itemId,
                    SampleFiles::read(__DIR__ . '/Samples/place.xml'),
                    $cdbXmlNamespaceUri
                ),
                RelationType::place(),
            ],
            [
                $itemId,
                new OrganizerUpdatedFromUDB2(
                    $itemId,
                    SampleFiles::read(__DIR__ . '/Samples/organizer.xml'),
                    $cdbXmlNamespaceUri
                ),
                RelationType::organizer(),
            ],
            [
                $itemId,
                new OrganizerUpdatedFromUDB2(
                    $itemId,
                    SampleFiles::read(__DIR__ . '/Samples/organizer_with_same_label_but_different_casing.xml'),
                    $cdbXmlNamespaceUri
                ),
                RelationType::organizer(),
            ],
            [
                $itemId,
                new OrganizerUpdatedFromUDB2(
                    $itemId,
                    SampleFiles::read(__DIR__ . '/Samples/organizer_with_same_label_but_different_casing_and_visibility.xml'),
                    $cdbXmlNamespaceUri
                ),
                RelationType::organizer(),
            ],
        ];
    }

    private function getRelationId(): string
    {
        return 'E4CA9DB5-DEE3-42F0-B04A-547DFC3CB2EE';
    }

    private function createDomainMessage(string $id, Serializable $payload): DomainMessage
    {
        return new DomainMessage(
            $id,
            0,
            new Metadata(),
            $payload,
            BroadwayDateTime::now()
        );
    }
}
