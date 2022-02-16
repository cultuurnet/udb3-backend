<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Label\Events\AbstractEvent;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\Events\AbstractLabelEvent;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use CultuurNet\UDB3\StringLiteral;

class ItemVisibilityProjectorTest extends TestCase
{
    /**
     * @var ItemVisibilityProjector
     */
    private $projector;

    /**
     * @var DocumentRepository|MockObject
     */
    private $itemRepository;

    /**
     * @var ReadRepositoryInterface|MockObject
     */
    private $relationRepository;

    protected function setUp()
    {
        $this->itemRepository = $this->createMock(DocumentRepository::class);
        $this->relationRepository = $this->createMock(ReadRepositoryInterface::class);

        $this->projector = new ItemVisibilityProjector(
            $this->itemRepository,
            $this->relationRepository
        );
    }


    private function mockRelatedDocument(
        LabelName $labelName,
        JsonDocument $jsonDocument,
        RelationType $relationType
    ) {
        $this->relationRepository
            ->expects($this->once())
            ->method('getLabelRelations')
            ->with($labelName)
            ->willReturn(
                [
                    new LabelRelation(
                        $labelName,
                        $relationType,
                        new StringLiteral($jsonDocument->getId()),
                        false
                    ),
                ]
            );

        $this->itemRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($jsonDocument->getId())
            ->willReturn($jsonDocument);
    }

    /**
     * @test
     * @dataProvider relationTypeProvider
     */
    public function it_should_update_the_projection_of_offers_which_have_a_label_made_visible(
        RelationType $relationType
    ) {
        $labelId = new UUID('3960ff99-ceab-4b44-aa51-dc7a187b77e0');
        $labelName = new LabelName('black');
        $placeId = new StringLiteral('B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A');
        $madeVisibleEvent = new MadeVisible($labelId, $labelName);

        $existingPlaceDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'hiddenLabels' => ['green', 'black'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingPlaceDocument,
            $relationType
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $expectedDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'hiddenLabels' => ['green'],
                    'labels' => ['black'],
                ]
            )
        );

        $this->itemRepository
            ->expects($this->once())
            ->method('save')
            ->with($expectedDocument);

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
    public function it_updates_the_projection_of_offers_which_have_a_label_made_invisible()
    {
        $labelId = new UUID('3e6aa636-ec87-4f66-b6a5-4f8135120b28');
        $labelName = new LabelName('black');
        $placeId = new StringLiteral('B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A');
        $madeInvisibleEvent = new MadeInvisible($labelId, $labelName);

        $existingPlaceDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'labels' => ['green', 'black'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingPlaceDocument,
            RelationType::place()
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeInvisibleEvent
        );

        $expectedDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'labels' => ['green'],
                    'hiddenLabels' => ['black'],
                ]
            )
        );

        $this->itemRepository
            ->expects($this->once())
            ->method('save')
            ->with($expectedDocument);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_remove_the_hidden_labels_property_of_an_offer_when_the_last_hidden_label_is_made_visible()
    {
        $labelId = new UUID('0b8f148e-713f-4986-9170-bdb23f3ff0d7');
        $labelName = new LabelName('black');
        $placeId = new StringLiteral('B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A');
        $madeVisibleEvent = new MadeVisible($labelId, $labelName);

        $existingPlaceDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'labels' => ['orange', 'green'],
                    'hiddenLabels' => ['black'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingPlaceDocument,
            RelationType::place()
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $expectedDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'labels' => ['orange', 'green', 'black'],
                ]
            )
        );

        $this->itemRepository
            ->expects($this->once())
            ->method('save')
            ->with($expectedDocument);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_update_the_projection_of_offers_which_have_a_label_made_invisible()
    {
        $labelId = new UUID('aabf18cd-00dd-4327-9d8b-8179b4a7c36a');
        $labelName = new LabelName('black');
        $placeId = new StringLiteral('B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A');
        $madeVisibleEvent = new MadeInvisible($labelId, $labelName);

        $existingPlaceDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'labels' => ['orange', 'black'],
                    'hiddenLabels' => ['green'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingPlaceDocument,
            RelationType::place()
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $expectedDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'labels' => ['orange'],
                    'hiddenLabels' => ['green', 'black'],
                ]
            )
        );

        $this->itemRepository
            ->expects($this->once())
            ->method('save')
            ->with($expectedDocument);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_remove_the_labels_property_of_an_offer_when_the_last_shown_label_is_made_invisible()
    {
        $labelId = new UUID('b2b756eb-09aa-4c03-b284-008a2b1cd8f7');
        $labelName = new LabelName('black');
        $placeId = new StringLiteral('B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A');
        $madeVisibleEvent = new MadeInvisible($labelId, $labelName);

        $existingPlaceDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'labels' => ['black'],
                    'hiddenLabels' => ['orange'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingPlaceDocument,
            RelationType::place()
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $expectedDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'hiddenLabels' => ['orange', 'black'],
                ]
            )
        );

        $this->itemRepository
            ->expects($this->once())
            ->method('save')
            ->with($expectedDocument);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_keeps_a_flat_label_array_when_modifying_label_visibility()
    {
        $labelId = new UUID('2bc515a3-4aea-4457-999d-f3822b601651');
        $labelName = new LabelName('black');
        $placeId = new StringLiteral('B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A');
        $madeVisibleEvent = new MadeInvisible($labelId, $labelName);

        $existingPlaceDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'labels' => ['black', 'red', 'green'],
                    'hiddenLabels' => ['orange', 'blue', 'purple'],
                ]
            )
        );

        $this->mockRelatedDocument(
            $labelName,
            $existingPlaceDocument,
            RelationType::place()
        );

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $expectedDocument = new JsonDocument(
            (string) $placeId,
            json_encode(
                (object) [
                    'labels' => ['red', 'green'],
                    'hiddenLabels' => ['orange', 'blue', 'purple', 'black'],
                ]
            )
        );

        $this->itemRepository
            ->expects($this->once())
            ->method('save')
            ->with($expectedDocument);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_log_the_absence_of_an_offer_document_when_the_visibility_of_its_labels_changes()
    {
        $labelId = new UUID('1d9a5bb0-3c57-4d9c-af3e-e7978d5b737f');
        $labelName = new LabelName('foo');
        $placeId = new StringLiteral('B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A');
        $madeVisibleEvent = new MadeInvisible($labelId, $labelName);

        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->createMock(AbstractLogger::class);
        $logger
            ->expects($this->once())
            ->method('alert')
            ->with('Can not update visibility of label: "' . $labelName . '" for the relation with id: "B8A3FF1E-64A3-41C4-A2DB-A6FA35E4219A" because the document could not be retrieved.');

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
                        new StringLiteral((string) $placeId),
                        false
                    ),
                ]
            );

        $this->itemRepository
            ->expects($this->once())
            ->method('fetch')
            ->willThrowException(DocumentDoesNotExist::withId((string) $placeId));

        $domainMessage = $this->createDomainMessage(
            $labelId->toString(),
            $madeVisibleEvent
        );

        $this->projector->handle($domainMessage);
    }

    /**
     * @param string $id
     * @param AbstractEvent|AbstractLabelEvent $payload
     * @return DomainMessage
     */
    private function createDomainMessage($id, $payload)
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
