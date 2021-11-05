<?php

declare(strict_types=1);

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
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteOrganizer;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;

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
            (new UUID())->toNative(),
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
