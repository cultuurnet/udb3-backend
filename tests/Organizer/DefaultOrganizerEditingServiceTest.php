<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventStore\InMemoryEventStore;
use Broadway\EventStore\TraceableEventStore;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\ContactPoint;
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
     * @var CommandBus|MockObject
     */
    private $commandBus;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    protected TraceableEventStore $eventStore;

    private Repository $organizerRepository;

    private DefaultOrganizerEditingService $service;

    public function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);

        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->uuidGenerator->method('generate')
            ->willReturn('9196cb78-4381-11e6-beb8-9e71128cae77');

        $this->eventStore = new TraceableEventStore(new InMemoryEventStore());

        $this->organizerRepository = new OrganizerRepository(
            $this->eventStore,
            new SimpleEventBus()
        );

        $this->service = new DefaultOrganizerEditingService(
            $this->commandBus,
            $this->uuidGenerator,
            $this->organizerRepository
        );
    }

    /**
     * @test
     */
    public function it_can_create_an_organizer_with_a_unique_website(): void
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
                    'en',
                    'http://www.stuk.be',
                    'Het Stuk'
                ),
            ],
            $this->eventStore->getEvents()
        );

        $this->assertEquals($expectedUuid, $organizerId);
    }

    /**
     * @test
     */
    public function it_can_create_an_organizer_with_a_unique_website_plus_contact_point_and_address(): void
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
                    'en',
                    'http://www.stuk.be',
                    'Het Stuk'
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
}
