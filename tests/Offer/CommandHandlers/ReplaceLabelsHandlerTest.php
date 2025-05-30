<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\LabelsReplaced;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Label\LabelImportPreProcessor;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\ReplaceLabels;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;
use PHPUnit\Framework\MockObject\MockObject;

final class ReplaceLabelsHandlerTest extends CommandHandlerScenarioTestCase
{
    private MockObject $labelService;

    private MockObject $labelPermissionRepository;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): ReplaceLabelsHandler
    {
        $this->labelService = $this->createMock(LabelServiceInterface::class);

        $this->labelPermissionRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->labelPermissionRepository
            ->method('canUseLabel')
            ->willReturnCallback(
                function (string $userId, string $labelName) {
                    return $labelName !== 'not_allowed';
                }
            );

        return new ReplaceLabelsHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            ),
            new LabelImportPreProcessor(
                $this->labelService,
                $this->labelPermissionRepository,
                'b4ac44f4-31d0-4dcd-968e-c01538f117d8'
            )
        );
    }

    /**
     * @test
     */
    public function it_should_replace_labels_and_also_record_label_added_events(): void
    {
        $this->labelService->expects($this->exactly(2))
            ->method('createLabelAggregateIfNew')
            ->withConsecutive(
                [
                    new LabelName('foo'),
                    true,
                ],
                [
                    new LabelName('bar'),
                    false,
                ]
            );

        $this->labelPermissionRepository->expects($this->any())
            ->method('getByName')
            ->willReturnCallback(
                function ($labelName) {
                    return new Entity(
                        Uuid::uuid4(),
                        $labelName,
                        $labelName !== 'bar' ? Visibility::visible() : Visibility::invisible(),
                        Privacy::public()
                    );
                }
            );

        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->eventCreated($id)])
            ->when(
                new ReplaceLabels(
                    $id,
                    new Labels(
                        new Label(
                            new LabelName('foo'),
                            true
                        ),
                        new Label(
                            new LabelName('bar'),
                            false
                        )
                    )
                )
            )
            ->then(
                [
                    new LabelsReplaced(
                        $id,
                        ['foo'],
                        ['bar']
                    ),
                    new LabelAdded($id, 'foo', true),
                    new LabelAdded($id, 'bar', false),
                ]
            );
    }

    /**
     * @test
     */
    public function it_does_not_add_a_private_label_if_not_allowed(): void
    {
        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->eventCreated($id),
                ]
            )
            ->when(
                (new ReplaceLabels(
                    $id,
                    new Labels(new Label(new LabelName('not_allowed')))
                ))
            )
            ->then([
                new LabelsReplaced(
                    $id,
                    [],
                    []
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_should_not_remove_private_labels_that_are_already_on_the_offer_via_replace(): void
    {
        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->eventCreated($id),
                    new LabelsReplaced(
                        $id,
                        [
                            'not_allowed',
                            'allowed',
                        ],
                        []
                    ),
                    new LabelAdded($id, 'not_allowed'),
                    new LabelAdded($id, 'allowed'),
                ]
            )
            ->when(
                new ReplaceLabels($id, new Labels())
            )
            ->then([
                new LabelsReplaced($id, [], []),
                new LabelRemoved($id, 'allowed'),
            ]);
    }

    /**
     * @test
     */
    public function it_should_remove_labels_when_doing_an_empty_replace_labels(): void
    {
        $this->labelService->expects($this->never())
            ->method('createLabelAggregateIfNew');

        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->eventCreated($id),
                    new LabelAdded($id, 'label 1'),
                    new LabelAdded($id, 'label 2'),
                ]
            )
            ->when(new ReplaceLabels($id, new Labels()))
            ->then([
                new LabelsReplaced($id, [], []),
                new LabelRemoved($id, 'label 1'),
                new LabelRemoved($id, 'label 2'),
            ]);
    }

    private function eventCreated(string $id): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            'some representative title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new PermanentCalendar(new OpeningHours())
        );
    }
}
