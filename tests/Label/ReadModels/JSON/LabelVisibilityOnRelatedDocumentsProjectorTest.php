<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\Events\AbstractEvent;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class LabelVisibilityOnRelatedDocumentsProjectorTest extends TestCase
{
    private LabelVisibilityOnRelatedDocumentsProjector $projector;
    private MockObject $eventDocumentRepository;
    private MockObject $placeDocumentRepository;
    private MockObject $organizerDocumentRepository;
    private MockObject $relationRepository;

    private array $mockedEvents = [];
    private array $mockedPlaces = [];
    private array $mockedOrganizers = [];

    protected function setUp(): void
    {
        $this->relationRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->eventDocumentRepository = $this->createMock(DocumentRepository::class);
        $this->placeDocumentRepository = $this->createMock(DocumentRepository::class);
        $this->organizerDocumentRepository = $this->createMock(DocumentRepository::class);

        $fetch = static function (array $mockedDocuments, string $id): JsonDocument {
            if (isset($mockedDocuments[$id])) {
                return $mockedDocuments[$id];
            }
            throw DocumentDoesNotExist::withId($id);
        };

        $this->eventDocumentRepository->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(fn (string $id) => $fetch($this->mockedEvents, $id));

        $this->placeDocumentRepository->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(fn (string $id) => $fetch($this->mockedPlaces, $id));

        $this->organizerDocumentRepository->expects($this->any())
            ->method('fetch')
            ->willReturnCallback(fn (string $id) => $fetch($this->mockedOrganizers, $id));

        $this->projector = (new LabelVisibilityOnRelatedDocumentsProjector($this->relationRepository))
            ->withDocumentRepositoryForRelationType(RelationType::event(), $this->eventDocumentRepository)
            ->withDocumentRepositoryForRelationType(RelationType::place(), $this->placeDocumentRepository)
            ->withDocumentRepositoryForRelationType(RelationType::organizer(), $this->organizerDocumentRepository);
    }

    private function mockRelatedDocument(
        string $labelName,
        JsonDocument $jsonDocument,
        RelationType $relationType
    ): void {
        $this->relationRepository
            ->expects($this->once())
            ->method('getLabelRelations')
            ->with($labelName)
            ->willReturn(
                [
                    new LabelRelation(
                        $labelName,
                        $relationType,
                        $jsonDocument->getId(),
                        false
                    ),
                ]
            );

        if ($relationType->sameAs(RelationType::event())) {
            $this->mockedEvents[$jsonDocument->getId()] = $jsonDocument;
        }
        if ($relationType->sameAs(RelationType::place())) {
            $this->mockedPlaces[$jsonDocument->getId()] = $jsonDocument;
        }
        if ($relationType->sameAs(RelationType::organizer())) {
            $this->mockedOrganizers[$jsonDocument->getId()] = $jsonDocument;
        }
    }

    private function expectDocumentUpdate(RelationType $relationType, JsonDocument $expectedDocument): void
    {
        if ($relationType->sameAs(RelationType::event())) {
            $this->eventDocumentRepository
                ->expects($this->once())
                ->method('save')
                ->with($expectedDocument);
        }
        if ($relationType->sameAs(RelationType::place())) {
            $this->placeDocumentRepository
                ->expects($this->once())
                ->method('save')
                ->with($expectedDocument);
        }
        if ($relationType->sameAs(RelationType::organizer())) {
            $this->organizerDocumentRepository
                ->expects($this->once())
                ->method('save')
                ->with($expectedDocument);
        }
    }

    /**
     * @test
     * @dataProvider relationTypeProvider
     */
    public function it_should_update_the_projection_of_documents_which_have_a_label_made_visible(
        RelationType $relationType
    ): void {
        $labelId = new Uuid('3960ff99-ceab-4b44-aa51-dc7a187b77e0');
        $labelName = 'black';
        $documentId = 'B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A';
        $madeVisibleEvent = new MadeVisible($labelId, $labelName);

        $existingDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'hiddenLabels' => ['green', 'black'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingDocument,
            $relationType
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $expectedDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'hiddenLabels' => ['green'],
                    'labels' => ['black'],
                ]
            )
        );

        $this->expectDocumentUpdate($relationType, $expectedDocument);

        $this->projector->handle($domainMessage);
    }

    /**
     * @return RelationType[][]
     */
    public function relationTypeProvider(): array
    {
        return [
            [
                RelationType::event(),
            ],
            [
                RelationType::place(),
            ],
            [
                RelationType::organizer(),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_updates_the_projection_of_documents_which_have_a_label_made_invisible(): void
    {
        $labelId = new Uuid('3e6aa636-ec87-4f66-b6a5-4f8135120b28');
        $labelName = 'black';
        $documentId = 'B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A';
        $madeInvisibleEvent = new MadeInvisible($labelId, $labelName);

        $existingDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'labels' => ['green', 'black'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingDocument,
            RelationType::place()
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeInvisibleEvent
        );

        $expectedDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'labels' => ['green'],
                    'hiddenLabels' => ['black'],
                ]
            )
        );

        $this->expectDocumentUpdate(RelationType::place(), $expectedDocument);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_remove_the_hidden_labels_property_of_a_document_when_the_last_hidden_label_is_made_visible(): void
    {
        $labelId = new Uuid('0b8f148e-713f-4986-9170-bdb23f3ff0d7');
        $labelName = 'black';
        $documentId = 'B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A';
        $madeVisibleEvent = new MadeVisible($labelId, $labelName);

        $existingDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'labels' => ['orange', 'green'],
                    'hiddenLabels' => ['black'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingDocument,
            RelationType::place()
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $expectedDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'labels' => ['orange', 'green', 'black'],
                ]
            )
        );

        $this->expectDocumentUpdate(RelationType::place(), $expectedDocument);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_update_the_projection_of_documents_which_have_a_label_made_invisible(): void
    {
        $labelId = new Uuid('aabf18cd-00dd-4327-9d8b-8179b4a7c36a');
        $labelName = 'black';
        $documentId ='B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A';
        $madeVisibleEvent = new MadeInvisible($labelId, $labelName);

        $existingDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'labels' => ['orange', 'black'],
                    'hiddenLabels' => ['green'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingDocument,
            RelationType::place()
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $expectedDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'labels' => ['orange'],
                    'hiddenLabels' => ['green', 'black'],
                ]
            )
        );

        $this->expectDocumentUpdate(RelationType::place(), $expectedDocument);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_remove_the_labels_property_of_a_document_when_the_last_shown_label_is_made_invisible(): void
    {
        $labelId = new Uuid('b2b756eb-09aa-4c03-b284-008a2b1cd8f7');
        $labelName = 'black';
        $documentId = 'B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A';
        $madeVisibleEvent = new MadeInvisible($labelId, $labelName);

        $existingDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'labels' => ['black'],
                    'hiddenLabels' => ['orange'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingDocument,
            RelationType::place()
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $expectedDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'hiddenLabels' => ['orange', 'black'],
                ]
            )
        );

        $this->expectDocumentUpdate(RelationType::place(), $expectedDocument);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_keeps_a_flat_label_array_when_modifying_label_visibility(): void
    {
        $labelId = new Uuid('2bc515a3-4aea-4457-999d-f3822b601651');
        $labelName = 'black';
        $documentId = 'B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A';
        $madeVisibleEvent = new MadeInvisible($labelId, $labelName);

        $existingDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'labels' => ['black', 'red', 'green'],
                    'hiddenLabels' => ['orange', 'blue', 'purple'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingDocument,
            RelationType::place()
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $expectedDocument = new JsonDocument(
            $documentId,
            Json::encode(
                (object) [
                    'labels' => ['red', 'green'],
                    'hiddenLabels' => ['orange', 'blue', 'purple', 'black'],
                ]
            )
        );

        $this->expectDocumentUpdate(RelationType::place(), $expectedDocument);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_log_the_absence_of_a_document_when_the_visibility_of_its_labels_changes(): void
    {
        $labelId = new Uuid('1d9a5bb0-3c57-4d9c-af3e-e7978d5b737f');
        $labelName = 'foo';
        $documentId = 'B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A';
        $madeVisibleEvent = new MadeInvisible($labelId, $labelName);

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(AbstractLogger::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Can not update visibility of label: "' . $labelName . '" for the relation with id "B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A" because the document could not be retrieved.');

        $this->projector->setLogger($logger);

        $this->relationRepository
            ->expects($this->once())
            ->method('getLabelRelations')
            ->with($labelName)
            ->willReturn(
                [
                    new LabelRelation(
                        $labelName,
                        RelationType::place(),
                        $documentId,
                        false
                    ),
                ]
            );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $this->projector->handle($domainMessage);
    }

    private function createDomainMessage(string $id, AbstractEvent $payload): DomainMessage
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
