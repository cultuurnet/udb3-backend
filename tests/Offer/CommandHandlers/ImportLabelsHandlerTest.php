<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventRepository;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\LabelsImported;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label as Udb3ModelsLabel;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName as Udb3ModelsLabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels as Udb3ModelsLabels;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Place\PlaceRepository;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;

final class ImportLabelsHandlerTest extends CommandHandlerScenarioTestCase
{
    private MockObject $labelService;

    private MockObject $lockedLabelRepository;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): ImportLabelsHandler
    {
        $this->labelService = $this->createMock(LabelServiceInterface::class);
        $this->lockedLabelRepository = $this->createMock(LockedLabelRepository::class);

        $labelPermissionRepository = $this->createMock(ReadRepositoryInterface::class);
        $labelPermissionRepository->expects($this->any())
            ->method('canUseLabel')
            ->willReturnCallback(
                function (StringLiteral $userId, StringLiteral $labelName) {
                    return $labelName->toNative() !== 'not_allowed';
                }
            );

        return new ImportLabelsHandler(
            new OfferRepository(
                new EventRepository($eventStore, $eventBus),
                new PlaceRepository($eventStore, $eventBus)
            ),
            $this->labelService,
            $labelPermissionRepository,
            $this->lockedLabelRepository,
            'b4ac44f4-31d0-4dcd-968e-c01538f117d8'
        );
    }

    /**
     * @test
     */
    public function it_should_import_labels_and_also_record_label_added_events(): void
    {
        $this->labelService->expects($this->at(0))
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('foo'), true);

        $this->labelService->expects($this->at(1))
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('bar'), false);

        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given([$this->eventCreated($id)])
            ->when(
                new ImportLabels(
                    $id,
                    new Udb3ModelsLabels(
                        new Udb3ModelsLabel(
                            new Udb3ModelsLabelName('foo'),
                            true
                        ),
                        new Udb3ModelsLabel(
                            new Udb3ModelsLabelName('bar'),
                            false
                        )
                    )
                )
            )
            ->then(
                [
                    new LabelsImported(
                        $id,
                        new Udb3ModelsLabels(
                            new Udb3ModelsLabel(
                                new Udb3ModelsLabelName('foo'),
                                true
                            ),
                            new Udb3ModelsLabel(
                                new Udb3ModelsLabelName('bar'),
                                false
                            )
                        )
                    ),
                    new LabelAdded($id, new Label('foo', true)),
                    new LabelAdded($id, new Label('bar', false)),
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
                (new ImportLabels(
                    $id,
                    new Udb3ModelsLabels(new Udb3ModelsLabel(new Udb3ModelsLabelName('not_allowed')))
                )
                )
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_should_not_replace_private_labels_that_are_already_on_the_offer(): void
    {
        $this->labelService->expects($this->never())
            ->method('createLabelAggregateIfNew');

        $this->lockedLabelRepository->expects($this->any())
            ->method('getLockedLabelsForItem')
            ->willReturn(new Udb3ModelsLabels(new Udb3ModelsLabel(new Udb3ModelsLabelName('private'))));

        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->eventCreated($id),
                    new LabelAdded($id, new Label('not_private')),
                    new LabelAdded($id, new Label('private')),
                ]
            )
            ->when(
                new ImportLabels($id, new Udb3ModelsLabels())
            )
            ->then([new LabelRemoved($id, new Label('not_private'))]);
    }

    /**
     * @test
     */
    public function it_should_not_remove_labels_if_not_explicitly_instructed_to(): void
    {
        $this->labelService->expects($this->never())
            ->method('createLabelAggregateIfNew');

        $id = '39007d2d-acec-438d-a687-f2d8400d4c1e';

        $this->lockedLabelRepository->expects($this->any())
            ->method('getLockedLabelsForItem')
            ->willReturn(new Udb3ModelsLabels(
                new Udb3ModelsLabel(new Udb3ModelsLabelName('label 1')),
                new Udb3ModelsLabel(new Udb3ModelsLabelName('label 2'))
            ));

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->eventCreated($id),
                    new LabelAdded($id, new Label('label 1')),
                    new LabelAdded($id, new Label('label 2')),
                ]
            )
            ->when(new ImportLabels($id, new Udb3ModelsLabels()))
            ->then([]);
    }

    private function eventCreated(string $id): EventCreated
    {
        return new EventCreated(
            $id,
            new Language('nl'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('d0cd4e9d-3cf1-4324-9835-2bfba63ac015'),
            new Calendar(CalendarType::PERMANENT())
        );
    }
}
