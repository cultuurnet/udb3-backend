<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
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
use ValueObjects\StringLiteral\StringLiteral;

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
                function (StringLiteral $userId, StringLiteral $labelName) {
                    return $labelName->toNative() !== 'not_allowed';
                }
            );

        $this->labelService = $this->createMock(LabelServiceInterface::class);

        return new ImportLabelsHandler(
            new OrganizerRepository(
                $eventStore,
                $eventBus
            ),
            $this->labelService,
            $labelPermissionRepository,
            'b4ac44f4-31d0-4dcd-968e-c01538f117d8'
        );
    }

    /**
     * @test
     */
    public function it_handles_label_imports(): void
    {
        $this->labelService->expects($this->at(0))
            ->method('createLabelAggregateIfNew')
            ->with(new LegacyLabelName('foo'), true);

        $this->labelService->expects($this->at(1))
            ->method('createLabelAggregateIfNew')
            ->with(new LegacyLabelName('bar'), true);

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
                    ),
                    new LabelAdded($id, 'foo'),
                    new LabelAdded($id, 'bar'),
                ]
            );
    }

    /**
     * @test
     */
    public function it_throws_when_trying_to_add_a_private_label(): void
    {
        $id = '86a51894-e18e-4a6a-b7c5-d774e8c81074';

        $this->assertCallableThrowsApiProblem(
            ApiProblem::labelNotAllowed('not_allowed'),
            fn () => $this->scenario
                ->withAggregateId($id)
                ->given(
                    [
                        $this->organizerCreated($id),
                    ]
                )
                ->when(
                    (new ImportLabels($id, new Labels(new Label(new LabelName('not_allowed')))))
                )
                ->then([])
        );
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
                    new LabelAdded($id, 'not_allowed')
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
                        new Labels(
                            new Label(new LabelName('allowed'))
                        )
                    ),
                    new LabelAdded($id, 'allowed')
                ]
            );
    }

    /**
     * @test
     */
    public function it_will_not_replace_private_labels_that_are_already_on_the_organizer(): void
    {
        $this->labelService
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('existing_private'), true);

        $id = '86a51894-e18e-4a6a-b7c5-d774e8c81074';

        $this->scenario
            ->withAggregateId($id)
            ->given(
                [
                    $this->organizerCreated($id),
                    new LabelAdded($id, 'existing_to_be_removed'),
                    new LabelAdded($id, 'existing_private'),
                ]
            )
            ->when(
                (new ImportLabels($id, new Labels()))
                    ->withLabelsToKeepIfAlreadyOnOrganizer(
                        new Labels(
                            new Label(
                                new LabelName('existing_private')
                            )
                        )
                    )
            )
            ->then(
                [
                    new LabelRemoved($id, 'existing_to_be_removed'),
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
