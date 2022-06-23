<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Label\LabelImportPreProcessor;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\LabelsImported;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use PHPUnit\Framework\MockObject\MockObject;

final class ImportLabelsHandlerTest extends CommandHandlerScenarioTestCase
{
    use AssertApiProblemTrait;

    private MockObject $labelService;

    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): ImportLabelsHandler
    {
        $labelPermissionRepository = $this->createMock(ReadRepositoryInterface::class);
        $labelPermissionRepository->expects($this->any())
            ->method('canUseLabel')
            ->willReturnCallback(
                function (string $userId, string $labelName) {
                    return $labelName !== 'not_allowed';
                }
            );

        $this->labelService = $this->createMock(LabelServiceInterface::class);

        return new ImportLabelsHandler(
            new OrganizerRepository(
                $eventStore,
                $eventBus
            ),
            new LabelImportPreProcessor(
                $this->labelService,
                $labelPermissionRepository,
                'b4ac44f4-31d0-4dcd-968e-c01538f117d8'
            )
        );
    }

    /**
     * @test
     */
    public function it_handles_label_imports(): void
    {
        $this->labelService->expects($this->at(0))
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('foo'), true);

        $this->labelService->expects($this->at(1))
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('bar'), true);

        $id = '86a51894-e18e-4a6a-b7c5-d774e8c81074';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->organizerCreated($id),
                ]
            )
            ->when(
                new ImportLabels(
                    $id,
                    new Labels(
                        new Label(
                            new LabelName('foo'),
                            true
                        ),
                        new Label(
                            new LabelName('bar'),
                            true
                        )
                    )
                )
            )
            ->then(
                [
                    new LabelsImported(
                        $id,
                        ['foo', 'bar'],
                        []
                    ),
                    new LabelAdded($id, 'foo'),
                    new LabelAdded($id, 'bar'),
                ]
            );
    }

    /**
     * @test
     */
    public function it_does_not_add_a_private_label_if_not_allowed(): void
    {
        $id = '86a51894-e18e-4a6a-b7c5-d774e8c81074';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->organizerCreated($id),
                ]
            )
            ->when(
                (new ImportLabels($id, new Labels(new Label(new LabelName('not_allowed')))))
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_does_not_throw_if_a_private_label_is_used_but_already_on_the_organizer_anyway(): void
    {
        $id = '86a51894-e18e-4a6a-b7c5-d774e8c81074';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->organizerCreated($id),
                    new LabelAdded($id, 'not_allowed'),
                ]
            )
            ->when(
                new ImportLabels(
                    $id,
                    new Labels(
                        new Label(new LabelName('not_allowed')),
                        new Label(new LabelName('allowed'))
                    )
                )
            )
            ->then(
                [
                    new LabelsImported(
                        $id,
                        ['allowed'],
                        []
                    ),
                    new LabelAdded($id, 'allowed'),
                ]
            );
    }

    /**
     * @test
     */
    public function it_will_not_remove_private_labels_that_are_already_on_the_organizer(): void
    {
        $id = '86a51894-e18e-4a6a-b7c5-d774e8c81074';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->organizerCreated($id),
                    new LabelAdded($id, 'not_allowed'),
                    new LabelsImported($id, ['existing_to_be_removed'], []),
                    new LabelAdded($id, 'existing_to_be_removed'),
                ]
            )
            ->when(
                new ImportLabels($id, new Labels())
            )
            ->then(
                [
                    new LabelRemoved($id, 'existing_to_be_removed'),
                ]
            );
    }

    /**
     * @test
     */
    public function it_will_not_remove_labels_that_were_not_imported_before(): void
    {
        $id = '86a51894-e18e-4a6a-b7c5-d774e8c81074';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->organizerCreated($id),
                    new LabelAdded($id, 'added_via_ui_1'),
                    new LabelsImported(
                        $id,
                        ['imported_1', 'imported_2'],
                        []
                    ),
                    new LabelAdded($id, 'imported_1'),
                    new LabelAdded($id, 'imported_2'),
                    new LabelAdded($id, 'added_via_ui_2'),
                ]
            )
            ->when(
                new ImportLabels($id, new Labels())
            )
            ->then(
                [
                    new LabelRemoved($id, 'imported_1'),
                    new LabelRemoved($id, 'imported_2'),
                ]
            );
    }

    private function organizerCreated(string $id): OrganizerCreated
    {
        return new OrganizerCreated(
            $id,
            'Organizer Title',
            'Kerkstraat 69',
            '9630',
            'Zottegem',
            'BE',
            ['phone'],
            ['email'],
            ['url']
        );
    }
}
