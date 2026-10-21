<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Repositories;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\TestCase;

final class DBALTicketingRegistrationRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private const LINK_A = 'https://schoolmetcultuur.ticketmatic.com/a';
    private const LINK_B = 'https://schoolmetcultuur.ticketmatic.com/b';

    private DBALTicketingRegistrationRepository $repository;
    private Uuid $eventId;
    private Uuid $otherEventId;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->repository = new DBALTicketingRegistrationRepository($this->getConnection());
        $this->eventId = new Uuid('9a9ec5fe-1e42-4c38-9b6f-60e7a2b3a1c1');
        $this->otherEventId = new Uuid('2f1c4e6a-8d4b-4f0e-9d2a-7c3b5e1f0a92');
    }

    /**
     * @test
     */
    public function it_finds_the_registrations_of_an_event_per_source(): void
    {
        $topLevel = new TicketingRegistration($this->eventId, new Url(self::LINK_A), RegistrationSource::TopLevel);
        $subEvent = new TicketingRegistration($this->eventId, new Url(self::LINK_A), RegistrationSource::SubEvent);
        $this->repository->add($topLevel);
        $this->repository->add($subEvent);

        $this->assertEquals(new TicketingRegistrations($topLevel), $this->repository->findByEvent($this->eventId, RegistrationSource::TopLevel));
        $this->assertEquals(new TicketingRegistrations($subEvent), $this->repository->findByEvent($this->eventId, RegistrationSource::SubEvent));
    }

    /**
     * @test
     */
    public function it_ignores_adding_a_registration_twice(): void
    {
        $registration = new TicketingRegistration($this->eventId, new Url(self::LINK_A), RegistrationSource::SubEvent);
        $this->repository->add($registration);
        $this->repository->add($registration);

        $this->assertEquals(new TicketingRegistrations($registration), $this->repository->findByEvent($this->eventId, RegistrationSource::SubEvent));
    }

    /**
     * @test
     */
    public function it_removes_one_registration(): void
    {
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_A), RegistrationSource::SubEvent));
        $kept = new TicketingRegistration($this->eventId, new Url(self::LINK_B), RegistrationSource::SubEvent);
        $this->repository->add($kept);

        $this->repository->remove($this->eventId, new Url(self::LINK_A), RegistrationSource::SubEvent);

        $this->assertEquals(new TicketingRegistrations($kept), $this->repository->findByEvent($this->eventId, RegistrationSource::SubEvent));
    }

    /**
     * @test
     */
    public function it_removes_all_registrations_of_an_event(): void
    {
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_A), RegistrationSource::TopLevel));
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_B), RegistrationSource::SubEvent));
        $other = new TicketingRegistration($this->otherEventId, new Url(self::LINK_A), RegistrationSource::TopLevel);
        $this->repository->add($other);

        $this->repository->removeAllOfEvent($this->eventId);

        $this->assertEquals(new TicketingRegistrations(), $this->repository->findByEvent($this->eventId, RegistrationSource::TopLevel));
        $this->assertEquals(new TicketingRegistrations(), $this->repository->findByEvent($this->eventId, RegistrationSource::SubEvent));
        $this->assertEquals(new TicketingRegistrations($other), $this->repository->findByEvent($this->otherEventId, RegistrationSource::TopLevel));
    }

    /**
     * @test
     */
    public function it_sets_the_identifier_on_every_row_of_the_event_with_that_link(): void
    {
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_A), RegistrationSource::TopLevel));
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_A), RegistrationSource::SubEvent));
        $this->repository->add(new TicketingRegistration($this->otherEventId, new Url(self::LINK_A), RegistrationSource::TopLevel));

        $this->repository->setIdentifier($this->eventId, new Url(self::LINK_A), 'ticketmatic:cultuurhuis:1001');

        $found = $this->repository->findByIdentifier('ticketmatic:cultuurhuis:1001');

        $this->assertCount(2, $found);
        $this->assertContainsEquals(
            new TicketingRegistration($this->eventId, new Url(self::LINK_A), RegistrationSource::TopLevel, 'ticketmatic:cultuurhuis:1001'),
            $found->toArray()
        );
        $this->assertContainsEquals(
            new TicketingRegistration($this->eventId, new Url(self::LINK_A), RegistrationSource::SubEvent, 'ticketmatic:cultuurhuis:1001'),
            $found->toArray()
        );
    }

    /**
     * @test
     */
    public function it_does_not_set_an_identifier_when_the_link_is_gone(): void
    {
        $this->repository->setIdentifier($this->eventId, new Url(self::LINK_A), 'ticketmatic:cultuurhuis:1001');

        $this->assertEquals(new TicketingRegistrations(), $this->repository->findByIdentifier('ticketmatic:cultuurhuis:1001'));
    }

    /**
     * @test
     */
    public function it_finds_the_registrations_of_several_events_by_identifier(): void
    {
        $original = new TicketingRegistration($this->eventId, new Url(self::LINK_A), RegistrationSource::SubEvent, 'ticketmatic:cultuurhuis:2002');
        $copy = new TicketingRegistration($this->otherEventId, new Url(self::LINK_A), RegistrationSource::SubEvent, 'ticketmatic:cultuurhuis:2002');
        $this->repository->add($original);
        $this->repository->add($copy);

        $found = $this->repository->findByIdentifier('ticketmatic:cultuurhuis:2002');

        $this->assertCount(2, $found);
        $this->assertContainsEquals($original, $found->toArray());
        $this->assertContainsEquals($copy, $found->toArray());
    }

    /**
     * @test
     */
    public function it_finds_the_registrations_without_identifier(): void
    {
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_A), RegistrationSource::TopLevel, 'ticketmatic:cultuurhuis:1001'));
        $unregistered = new TicketingRegistration($this->eventId, new Url(self::LINK_B), RegistrationSource::SubEvent);
        $this->repository->add($unregistered);

        $this->assertEquals(new TicketingRegistrations($unregistered), $this->repository->findWithoutIdentifier());
    }
}
