<?php

namespace CultuurNet\UDB3\Organizer;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteOrganizer;
use CultuurNet\UDB3\Organizer\Commands\CreateOrganizer;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\Commands\RemoveAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\Events\AddressRemoved;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;
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
     * @var EventBus|MockObject
     */
    private $eventBus;

    /**
     * @var OrganizerRepository
     */
    private $repository;

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
        $this->eventBus = $this->createMock(EventBus::class);
        $this->repository = new OrganizerRepository($this->eventStore, $this->eventBus);

        $this->eventOrganizerRelationService = $this->createMock(OrganizerRelationServiceInterface::class);
        $this->placeOrganizerRelationService = $this->createMock(OrganizerRelationServiceInterface::class);

        $this->commandHandler = (new OrganizerCommandHandler($this->repository))
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

    protected function createCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus
    ): CommandHandler {
        return new OrganizerCommandHandler(
            new OrganizerRepository(
                $eventStore,
                $eventBus
            )
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
    public function it_handles_remove_address_commands()
    {
        $organizerId = $this->organizerCreated->getOrganizerId();

        $address = new Address(
            new Street('Martelarenplein 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );

        $this->scenario
            ->withAggregateId($organizerId)
            ->given(
                [
                    $this->organizerCreated,
                    new AddressUpdated(
                        $organizerId,
                        $address
                    ),
                ]
            )
            ->when(
                new RemoveAddress(
                    $organizerId
                )
            )
            ->then(
                [
                    new AddressRemoved(
                        $organizerId
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
