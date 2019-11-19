<?php

namespace CultuurNet\UDB3\Organizer;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use Broadway\Repository\RepositoryInterface;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Organizer\Commands\AddLabel;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\Commands\RemoveLabel;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

class DefaultOrganizerEditingServiceTest extends TestCase
{
    /**
     * @var CommandBusInterface|MockObject
     */
    private $commandBus;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    /**
     * @var TraceableEventStore
     */
    protected $eventStore;

    /**
     * @var RepositoryInterface
     */
    private $organizerRepository;

    /**
     * @var LabelServiceInterface|MockObject
     */
    private $labelService;

    /**
     * @var DefaultOrganizerEditingService
     */
    private $service;

    public function setUp()
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->uuidGenerator->method('generate')
            ->willReturn('9196cb78-4381-11e6-beb8-9e71128cae77');

        $this->eventStore = new TraceableEventStore(new InMemoryEventStore());

        $this->organizerRepository = new OrganizerRepository(
            $this->eventStore,
            new SimpleEventBus
        );

        $this->labelService = $this->createMock(LabelServiceInterface::class);

        $this->service = new DefaultOrganizerEditingService(
            $this->commandBus,
            $this->uuidGenerator,
            $this->organizerRepository,
            $this->labelService
        );
    }

    /**
     * @test
     */
    public function it_can_create_an_organizer_with_a_unique_website()
    {
        $this->eventStore->trace();

        $organizerId = $this->service->create(
            new Language('en'),
            Url::fromNative('http://www.stuk.be'),
            new Title('Het Stuk')
        );

        $expectedUuid = '9196cb78-4381-11e6-beb8-9e71128cae77';

        $this->assertEquals(
            [
                new OrganizerCreatedWithUniqueWebsite(
                    '9196cb78-4381-11e6-beb8-9e71128cae77',
                    new Language('en'),
                    Url::fromNative('http://www.stuk.be'),
                    new Title('Het Stuk')
                ),
            ],
            $this->eventStore->getEvents()
        );

        $this->assertEquals($expectedUuid, $organizerId);
    }

    /**
     * @test
     */
    public function it_can_create_an_organizer_with_a_unique_website_plus_contact_point_and_address()
    {
        $this->eventStore->trace();

        $organizerId = $this->service->create(
            new Language('en'),
            Url::fromNative('http://www.stuk.be'),
            new Title('Het Stuk'),
            new Address(
                new Street('Wetstraat 1'),
                new PostalCode('1000'),
                new Locality('Brussel'),
                Country::fromNative('BE')
            ),
            new ContactPoint(['050/123'], ['test@test.be', 'test2@test.be'], ['http://www.google.be'])
        );

        $expectedUuid = '9196cb78-4381-11e6-beb8-9e71128cae77';

        $this->assertEquals(
            [
                new OrganizerCreatedWithUniqueWebsite(
                    '9196cb78-4381-11e6-beb8-9e71128cae77',
                    new Language('en'),
                    Url::fromNative('http://www.stuk.be'),
                    new Title('Het Stuk')
                ),
                new AddressUpdated(
                    '9196cb78-4381-11e6-beb8-9e71128cae77',
                    new Address(
                        new Street('Wetstraat 1'),
                        new PostalCode('1000'),
                        new Locality('Brussel'),
                        Country::fromNative('BE')
                    )
                ),
                new ContactPointUpdated(
                    '9196cb78-4381-11e6-beb8-9e71128cae77',
                    new ContactPoint(['050/123'], ['test@test.be', 'test2@test.be'], ['http://www.google.be'])
                ),
            ],
            $this->eventStore->getEvents()
        );

        $this->assertEquals($expectedUuid, $organizerId);
    }

    /**
     * @test
     */
    public function it_handles_update_website()
    {
        $organizerId = 'baee2963-e1ba-4777-a803-4c645c6fd31c';
        $website = Url::fromNative('http://www.depot.be');

        $expectedUpdateWebsite = new UpdateWebsite($organizerId, $website);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedUpdateWebsite);

        $this->service->updateWebsite($organizerId, $website);
    }

    /**
     * @test
     */
    public function it_handles_update_title()
    {
        $organizerId = 'baee2963-e1ba-4777-a803-4c645c6fd31c';
        $title = new Title('Het Depot');
        $language = new Language('nl');

        $expectedUpdateTitle = new UpdateTitle(
            $organizerId,
            $title,
            $language
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedUpdateTitle);

        $this->service->updateTitle($organizerId, $title, $language);
    }

    /**
     * @test
     */
    public function it_handles_update_address()
    {
        $organizerId = 'baee2963-e1ba-4777-a803-4c645c6fd31c';
        $address = new Address(
            new Street('Martelarenplein 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );
        $language = new Language('nl');

        $expectedUpdateAddress = new UpdateAddress(
            $organizerId,
            $address,
            $language
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedUpdateAddress);

        $this->service->updateAddress($organizerId, $address, $language);
    }

    /**
     * @test
     */
    public function it_handles_update_contact_point()
    {
        $organizerId = 'baee2963-e1ba-4777-a803-4c645c6fd31c';
        $contactPoint = new ContactPoint(
            [
                '01213456789',
            ],
            [
                'info@hetdepot.be',
            ]
        );

        $updateContactPoint = new UpdateContactPoint($organizerId, $contactPoint);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($updateContactPoint);

        $this->service->updateContactPoint($organizerId, $contactPoint);
    }

    /**
     * @test
     */
    public function it_sends_a_add_label_command()
    {
        $organizerId = 'organizerId';
        $label = new Label('foo');

        $expectedAddLabel = new AddLabel($organizerId, $label);

        $this->labelService->expects($this->once())
            ->method('createLabelAggregateIfNew')
            ->with(new LabelName('foo'));

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedAddLabel);

        $this->service->addLabel($organizerId, $label);
    }

    /**
     * @test
     */
    public function it_sends_a_remove_label_command()
    {
        $organizerId = 'organizerId';
        $label = new Label('foo');

        $expectedRemoveLabel = new RemoveLabel($organizerId, $label);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedRemoveLabel);

        $this->service->removeLabel($organizerId, $label);
    }

    /**
     * @test
     */
    public function it_sends_a_delete_command()
    {
        $id = '1234';

        $expectedCommand = new DeleteOrganizer($id);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand);

        $this->service->delete($id);
    }
}
