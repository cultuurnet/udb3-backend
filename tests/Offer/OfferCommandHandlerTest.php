<?php

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\MediaManager;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\Item\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Item\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Offer\Item\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\Item\Commands\RemoveLabel;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Approve;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Commands\Moderation\Reject;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateFacilities;
use CultuurNet\UDB3\Offer\Item\Commands\UpdateTitle;
use CultuurNet\UDB3\Offer\Item\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Offer\Item\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Offer\Item\Events\ItemCreated;
use CultuurNet\UDB3\Offer\Item\Events\LabelAdded;
use CultuurNet\UDB3\Offer\Item\Events\LabelRemoved;
use CultuurNet\UDB3\Offer\Item\Events\LabelsImported;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Approved;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Published;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Rejected;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerDeleted;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerUpdated;
use CultuurNet\UDB3\Offer\Item\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TitleTranslated;
use CultuurNet\UDB3\Offer\Item\ItemCommandHandler;
use CultuurNet\UDB3\Offer\Item\ItemRepository;
use CultuurNet\UDB3\Offer\Mock\Commands\AddLabel as AddLabelToSomethingElse;
use CultuurNet\UDB3\Offer\Mock\Commands\RemoveLabel as RemoveLabelFromSomethingElse;
use CultuurNet\UDB3\Offer\Mock\Commands\UpdateTitle as UpdateTitleOnSomethingElse;
use CultuurNet\UDB3\Offer\Mock\Commands\UpdatePriceInfo as UpdatePriceInfoOnSomethingElse;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Identity\UUID;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;

class OfferCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Label
     */
    protected $label;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var Title
     */
    protected $title;

    /**
     * @var StringLiteral
     */
    protected $description;

    /**
     * @var PriceInfo
     */
    protected $priceInfo;

    /**
     * @var ItemCreated
     */
    protected $itemCreated;

    /**
     * @var RepositoryInterface|MockObject
     */
    protected $organizerRepository;

    /**
     * @var ReadRepositoryInterface|MockObject
     */
    protected $labelRepository;

    /**
     * @var MediaManager|MockObject
     */
    protected $mediaManager;

    public function setUp()
    {
        parent::setUp();

        $this->id = '123';
        $this->label = new Label('foo');
        $this->language = new Language('en');
        $this->title = new Title('English title');
        $this->description = new StringLiteral('English description');

        $this->itemCreated = new ItemCreated(
            $this->id,
            new Language('nl')
        );

        $this->priceInfo = new PriceInfo(
            new BasePrice(
                Price::fromFloat(10.5),
                Currency::fromNative('EUR')
            )
        );
    }

    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus
    ) {
        $this->organizerRepository = $this->createMock(RepositoryInterface::class);

        $this->labelRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->labelRepository
            ->method('getByName')
            ->willReturnCallback(
                function (StringLiteral $name) {
                    if ($name->sameValueAs(new StringLiteral('foo'))) {
                        return new Entity(
                            new UUID(),
                            new StringLiteral('foo'),
                            Visibility::VISIBLE(),
                            Privacy::PRIVACY_PUBLIC()
                        );
                    }

                    return null;
                }
            );

        $this->mediaManager = $this->createMock(MediaManager::class);

        return new ItemCommandHandler(
            new ItemRepository($eventStore, $eventBus),
            $this->organizerRepository,
            $this->labelRepository,
            $this->mediaManager
        );
    }

    /**
     * @test
     */
    public function it_handles_add_label_commands_from_the_correct_namespace()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(
                new AddLabel($this->id, $this->label)
            )
            ->then(
                [
                    new LabelAdded($this->id, $this->label),
                ]
            );
    }

    /**
     * @test
     */
    public function it_can_add_labels_that_do_not_exist_and_uses_the_labels_visibility_from_the_command_in_that_case()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(
                new AddLabel($this->id, new Label('does_not_exist', true))
            )
            ->then(
                [
                    new LabelAdded($this->id, new Label('does_not_exist', true)),
                ]
            )
            ->when(
                new AddLabel($this->id, new Label('does_not_exist_hidden', false))
            )
            ->then(
                [
                    new LabelAdded($this->id, new Label('does_not_exist_hidden', false)),
                ]
            );
    }

    /**
     * @test
     */
    public function it_ignores_add_label_commands_from_incorrect_namespaces()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(
                new AddLabelToSomethingElse($this->id, $this->label)
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_remove_label_commands_from_the_correct_namespace()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                    new LabelAdded($this->id, $this->label),
                ]
            )
            ->when(
                new RemoveLabel($this->id, $this->label)
            )
            ->then(
                [
                    new LabelRemoved($this->id, $this->label),
                ]
            );
    }

    /**
     * @test
     */
    public function it_does_not_check_if_a_label_exists_when_removing_it(): void
    {
        $this->labelRepository->expects($this->never())
            ->method('getByName');

        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                    new LabelAdded($this->id, $this->label),
                ]
            )
            ->when(
                new RemoveLabel($this->id, $this->label)
            )
            ->then(
                [
                    new LabelRemoved($this->id, $this->label),
                ]
            );
    }

    /**
     * @test
     */
    public function it_ignores_remove_label_commands_from_incorrect_namespaces()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                    new LabelAdded($this->id, $this->label),
                ]
            )
            ->when(
                new RemoveLabelFromSomethingElse($this->id, $this->label)
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_import_labels()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(
                new ImportLabels(
                    $this->id,
                    new Labels(
                        new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                            new LabelName('foo'),
                            true
                        ),
                        new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                            new LabelName('bar'),
                            true
                        )
                    )
                )
            )
            ->then(
                [
                    new LabelsImported(
                        $this->id,
                        new Labels(
                            new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                                new LabelName('foo'),
                                true
                            ),
                            new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                                new LabelName('bar'),
                                true
                            )
                        )
                    ),
                    new LabelAdded($this->id, new Label('foo')),
                    new LabelAdded($this->id, new Label('bar')),
                ]
            );
    }

    /**
     * @test
     */
    public function it_will_not_replace_private_labels_that_are_already_on_the_offer()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                    new LabelAdded($this->id, new Label('existing_to_be_removed')),
                    new LabelAdded($this->id, new Label('existing_private')),
                ]
            )
            ->when(
                (
                    new ImportLabels(
                        $this->id,
                        new Labels()
                    )
                )->withLabelsToKeepIfAlreadyOnOffer(
                    new Labels(
                        new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                            new LabelName('existing_private'),
                            true
                        )
                    )
                )->withLabelsToRemoveWhenOnOffer(
                    new Labels(
                        new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                            new LabelName('existing_to_be_removed'),
                            true
                        )
                    )
                )
            )
            ->then(
                [
                    new LabelRemoved($this->id, new Label('existing_to_be_removed')),
                ]
            );
    }

    /**
     * @test
     */
    public function it_will_not_remove_labels_that_were_added_after_import()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                    new LabelAdded($this->id, new Label('existing_added_async_after_import')),
                    new LabelAdded($this->id, new Label('existing_private')),
                ]
            )
            ->when(
                (
                new ImportLabels(
                    $this->id,
                    new Labels()
                )
                )
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_translate_title_commands_from_the_correct_namespace()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(
                new UpdateTitle($this->id, $this->language, $this->title)
            )
            ->then(
                [
                    new TitleTranslated($this->id, $this->language, $this->title),
                ]
            );
    }

    /**
     * @test
     */
    public function it_ignores_translate_title_commands_from_incorrect_namespace()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(
                new UpdateTitleOnSomethingElse($this->id, $this->language, $this->title)
            )
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_price_info_commands_from_the_correct_namespace()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(new UpdatePriceInfo($this->id, $this->priceInfo))
            ->then(
                [
                    new PriceInfoUpdated($this->id, $this->priceInfo),
                ]
            );
    }

    /**
     * @test
     */
    public function it_does_not_update_price_info_if_there_were_no_changes()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                    new PriceInfoUpdated($this->id, $this->priceInfo),
                ]
            )
            ->when(new UpdatePriceInfo($this->id, $this->priceInfo))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_ignores_price_info_commands_from_incorrect_namespace()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                ]
            )
            ->when(new UpdatePriceInfoOnSomethingElse($this->id, $this->priceInfo))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_handles_approve_command_on_ready_for_validation_item()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new Approve($this->id))
            ->then([
                new Approved($this->id),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_flag_as_duplicate_command_on_ready_for_validation_item()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new FlagAsDuplicate($this->id))
            ->then([
                new FlaggedAsDuplicate($this->id),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_flag_as_inappropriate_command_on_ready_for_validation_item()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new FlagAsInappropriate($this->id))
            ->then([
                new FlaggedAsInappropriate($this->id),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_reject_command_on_ready_for_validation_item()
    {
        $reason = new StringLiteral('reject reason');

        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
                new Published($this->id, new \DateTime()),
            ])
            ->when(new Reject($this->id, $reason))
            ->then([
                new Rejected($this->id, $reason),
            ]);
    }

    /**
     * @test
     */
    public function it_can_update_facilities_of_a_place()
    {
        $facilities = [
            new Facility('facility1', 'facility label'),
        ];

        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->itemCreated,
            ])
            ->when(new UpdateFacilities($this->id, $facilities))
            ->then([
                new FacilitiesUpdated($this->id, $facilities),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_delete_current_organizer_commands()
    {
        $this->scenario
            ->withAggregateId($this->id)
            ->given(
                [
                    $this->itemCreated,
                    new OrganizerUpdated($this->id, '9f4cad43-8a2b-4475-870c-e02ef9741754'),
                ]
            )
            ->when(
                new DeleteCurrentOrganizer($this->id)
            )
            ->then(
                [
                    new OrganizerDeleted($this->id, '9f4cad43-8a2b-4475-870c-e02ef9741754'),
                ]
            );
    }
}
