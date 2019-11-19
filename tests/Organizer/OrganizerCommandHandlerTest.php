<?php

namespace CultuurNet\UDB3\Organizer;

use Broadway\CommandHandling\CommandHandlerInterface;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventStore\EventStoreInterface;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteOrganizer;
use CultuurNet\UDB3\Organizer\Commands\AddLabel;
use CultuurNet\UDB3\Organizer\Commands\CreateOrganizer;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\Commands\RemoveLabel;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\LabelsImported;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class OrganizerCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * @var Title
     */
    private $defaultTitle;

    /**
     * @var TraceableEventStore
     */
    private $eventStore;

    /**
     * @var EventBusInterface|MockObject
     */
    private $eventBus;

    /**
     * @var OrganizerRepository
     */
    private $repository;

    /**
     * @var ReadRepositoryInterface|MockObject
     */
    private $labelRepository;

    /**
     * @var OrganizerRelationServiceInterface|MockObject
     */
    private $eventOrganizerRelationService;

    /**
     * @var OrganizerRelationServiceInterface|MockObject
     */
    private $placeOrganizerRelationService;

    /**
     * @var OrganizerCommandHandler
     */
    private $commandHandler;

    /**
     * @var OrganizerCreated
     */
    private $organizerCreated;

    public function setUp()
    {
        $this->defaultTitle = new Title('Sample');

        $this->eventStore = new TraceableEventStore(
            new InMemoryEventStore()
        );
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->repository = new OrganizerRepository($this->eventStore, $this->eventBus);

        $this->labelRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->labelRepository->method('getByName')
            ->will($this->returnCallback(
                function (StringLiteral $labelName) {
                    return new Entity(
                        new UUID(),
                        $labelName,
                        $labelName->toNative() === 'foo' ? Visibility::VISIBLE() : Visibility::INVISIBLE(),
                        Privacy::PRIVACY_PUBLIC()
                    );
                }
            ));

        $this->eventOrganizerRelationService = $this->createMock(OrganizerRelationServiceInterface::class);
        $this->placeOrganizerRelationService = $this->createMock(OrganizerRelationServiceInterface::class);

        $this->commandHandler = (
            new OrganizerCommandHandler(
                $this->repository,
                $this->labelRepository
            )
        )
            ->withOrganizerRelationService($this->eventOrganizerRelationService)
            ->withOrganizerRelationService($this->placeOrganizerRelationService);

        $this->organizerCreated = new OrganizerCreated(
            new UUID(),
            new Title('Organizer Title'),
            [
                new Address(
                    new Street('Kerkstraat 69'),
                    new PostalCode('9630'),
                    new Locality('Zottegem'),
                    Country::fromNative('BE')
                ),
            ],
            ['phone'],
            ['email'],
            ['url']
        );

        parent::setUp();
    }

    /**
     * Create a command handler for the given scenario test case.
     *
     * @param EventStoreInterface $eventStore
     * @param EventBusInterface $eventBus
     *
     * @return CommandHandlerInterface
     */
    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus
    ) {
        return new OrganizerCommandHandler(
            new OrganizerRepository(
                $eventStore,
                $eventBus
            ),
            $this->labelRepository
        );
    }

    /**
     * @test
     */
    public function it_handles_create_organizer()
    {
        $id = new UUID();

        $this->scenario
            ->withAggregateId($id)
            ->when(
                new CreateOrganizer(
                    $id,
                    new Language('nl'),
                    Url::fromNative('http://www.depot.be'),
                    new Title('Het depot')
                )
            )
            ->then(
                [
                    new OrganizerCreatedWithUniqueWebsite(
                        $id,
                        new Language('nl'),
                        Url::fromNative('http://www.depot.be'),
                        new Title('Het depot')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_website()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();

        $this->scenario
            ->withAggregateId($organizerId)
            ->given(
                [
                    $this->organizerCreated,
                ]
            )
            ->when(
                new UpdateWebsite(
                    $organizerId,
                    Url::fromNative('http://www.depot.be')
                )
            )
            ->then(
                [
                    new WebsiteUpdated(
                        $organizerId,
                        Url::fromNative('http://www.depot.be')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_title()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();

        $this->scenario
            ->withAggregateId($organizerId)
            ->given(
                [
                    $this->organizerCreated,
                ]
            )
            ->when(
                new UpdateTitle(
                    $organizerId,
                    new Title('Het Depot'),
                    new Language('nl')
                )
            )
            ->then(
                [
                    new TitleUpdated(
                        $organizerId,
                        new Title('Het Depot')
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_address()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();

        $address = new Address(
            new Street('Martelarenplein 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $language = new Language('nl');

        $this->scenario
            ->withAggregateId($organizerId)
            ->given(
                [
                    $this->organizerCreated,
                ]
            )
            ->when(
                new UpdateAddress(
                    $organizerId,
                    $address,
                    $language
                )
            )
            ->then(
                [
                    new AddressUpdated(
                        $organizerId,
                        $address
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_update_contact_point()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();

        $contactPoint = new ContactPoint(
            [
                '0123456789',
            ],
            [
                'info@hetdepot.be',
            ]
        );

        $this->scenario
            ->withAggregateId($organizerId)
            ->given(
                [
                    $this->organizerCreated,
                ]
            )
            ->when(
                new UpdateContactPoint(
                    $organizerId,
                    $contactPoint
                )
            )
            ->then(
                [
                    new ContactPointUpdated(
                        $organizerId,
                        $contactPoint
                    ),
                ]
            );
    }

    /**
     * @test
     */
    public function it_handles_add_label()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();
        $label = new Label('foo', true);

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([$this->organizerCreated])
            ->when(new AddLabel($organizerId, $label))
            ->then([new LabelAdded($organizerId, $label)]);
    }

    /**
     * @test
     */
    public function it_handles_add_invisible_label()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();
        $label = new Label('bar', false);

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([$this->organizerCreated])
            ->when(new AddLabel($organizerId, $label))
            ->then([new LabelAdded($organizerId, $label)]);
    }

    /**
     * @test
     */
    public function it_does_not_add_the_same_label_twice()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();
        $label = new Label('foo', true);

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([
                $this->organizerCreated,
                new LabelAdded($organizerId, $label),
            ])
            ->when(new AddLabel($organizerId, $label))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_removes_an_attached_label()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();
        $label = new Label('foo', true);

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([
                $this->organizerCreated,
                new LabelAdded($organizerId, $label),
            ])
            ->when(new RemoveLabel($organizerId, $label))
            ->then([new LabelRemoved($organizerId, $label)]);
    }

    /**
     * @test
     */
    public function it_removes_an_attached_invisible_label()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();
        $label = new Label('bar', false);

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([
                $this->organizerCreated,
                new LabelAdded($organizerId, $label),
            ])
            ->when(new RemoveLabel($organizerId, $label))
            ->then([new LabelRemoved($organizerId, $label)]);
    }

    /**
     * @test
     */
    public function it_does_not_remove_a_missing_label()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();
        $label = new Label('foo');

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([$this->organizerCreated])
            ->when(new RemoveLabel($organizerId, $label))
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_handle_complex_label_scenario()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();
        $labelFoo = new Label('foo', true);
        $labelBar = new Label('bar', false);

        $this->scenario
            ->withAggregateId($organizerId)
            ->given([$this->organizerCreated])
            ->when(new AddLabel($organizerId, $labelFoo))
            ->when(new AddLabel($organizerId, $labelBar))
            ->when(new AddLabel($organizerId, $labelBar))
            ->when(new RemoveLabel($organizerId, $labelFoo))
            ->when(new RemoveLabel($organizerId, $labelBar))
            ->when(new RemoveLabel($organizerId, $labelBar))
            ->then([
                new LabelAdded($organizerId, $labelFoo),
                new LabelAdded($organizerId, $labelBar),
                new LabelRemoved($organizerId, $labelFoo),
                new LabelRemoved($organizerId, $labelBar),
            ]);
    }

    /**
     * @test
     */
    public function it_handles_import_labels()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();

        $this->scenario
            ->withAggregateId($organizerId)
            ->given(
                [
                    $this->organizerCreated,
                ]
            )
            ->when(
                new ImportLabels(
                    $organizerId,
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
                        $organizerId,
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
                    new LabelAdded($organizerId, new Label('foo')),
                    new LabelAdded($organizerId, new Label('bar')),
                ]
            );
    }

    /**
     * @test
     */
    public function it_will_not_replace_private_labels_that_are_already_on_the_organizer()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();

        $this->scenario
            ->withAggregateId($organizerId)
            ->given(
                [
                    $this->organizerCreated,
                    new LabelAdded($organizerId, new Label('existing_to_be_removed')),
                    new LabelAdded($organizerId, new Label('existing_private')),
                ]
            )
            ->when(
                (
                    new ImportLabels(
                        $organizerId,
                        new Labels()
                    )
                )->withLabelsToKeepIfAlreadyOnOrganizer(
                    new Labels(
                        new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                            new LabelName('existing_private')
                        )
                    )
                )
            )
            ->then(
                [
                    new LabelRemoved($organizerId, new Label('existing_to_be_removed')),
                ]
            );
    }


    /**
     * @test
     */
    public function it_handles_delete_commands()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();

        $this->scenario->withAggregateId($organizerId)
            ->given([$this->organizerCreated])
            ->when(new DeleteOrganizer($organizerId))
            ->then([new OrganizerDeleted($organizerId)]);
    }

    /**
     * @test
     * @dataProvider deleteFromOfferDataProvider
     *
     * @param AbstractDeleteOrganizer $deleteOrganizer
     */
    public function it_ignores_delete_from_offer_commands(AbstractDeleteOrganizer $deleteOrganizer)
    {
        $organizerId = $this->organizerCreated->getOrganizerId();

        $this->scenario->withAggregateId($organizerId)
            ->given([$this->organizerCreated])
            ->when($deleteOrganizer)
            ->then([]);
    }

    /**
     * @return array
     */
    public function deleteFromOfferDataProvider()
    {
        return [
            [
                new \CultuurNet\UDB3\Place\Commands\DeleteOrganizer('place-id', 'organizer-id'),
            ],
            [
                new \CultuurNet\UDB3\Event\Commands\DeleteOrganizer('place-id', 'organizer-id'),
            ],
        ];
    }
}
