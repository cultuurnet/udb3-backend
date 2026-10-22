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
    public function it_finds_the_registrations_of_an_event(): void
    {
        $linkA = new TicketingRegistration($this->eventId, new Url(self::LINK_A));
        $linkB = new TicketingRegistration($this->eventId, new Url(self::LINK_B));
        $this->repository->add($linkA);
        $this->repository->add($linkB);
        $this->repository->add(new TicketingRegistration($this->otherEventId, new Url(self::LINK_A)));

        $this->assertEquals(new TicketingRegistrations($linkA, $linkB), $this->repository->findByEvent($this->eventId));
    }

    /**
     * @test
     */
    public function it_ignores_adding_a_registration_twice(): void
    {
        $registration = new TicketingRegistration($this->eventId, new Url(self::LINK_A));
        $this->repository->add($registration);
        $this->repository->add($registration);

        $this->assertEquals(new TicketingRegistrations($registration), $this->repository->findByEvent($this->eventId));
    }

    /**
     * @test
     */
    public function it_removes_one_registration(): void
    {
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_A)));
        $kept = new TicketingRegistration($this->eventId, new Url(self::LINK_B));
        $this->repository->add($kept);

        $this->repository->remove($this->eventId, new Url(self::LINK_A));

        $this->assertEquals(new TicketingRegistrations($kept), $this->repository->findByEvent($this->eventId));
    }

    /**
     * @test
     */
    public function it_removes_all_registrations_of_an_event(): void
    {
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_A)));
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_B)));
        $other = new TicketingRegistration($this->otherEventId, new Url(self::LINK_A));
        $this->repository->add($other);

        $this->repository->removeAllOfEvent($this->eventId);

        $this->assertEquals(new TicketingRegistrations(), $this->repository->findByEvent($this->eventId));
        $this->assertEquals(new TicketingRegistrations($other), $this->repository->findByEvent($this->otherEventId));
    }

    /**
     * @test
     */
    public function it_sets_the_identifier_only_on_the_link_of_that_event(): void
    {
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_A)));
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_B)));
        $this->repository->add(new TicketingRegistration($this->otherEventId, new Url(self::LINK_A)));

        $this->repository->setIdentifier($this->eventId, new Url(self::LINK_A), 'ticketmatic:cultuurhuis:1001');

        $this->assertEquals(
            new TicketingRegistrations(
                new TicketingRegistration($this->eventId, new Url(self::LINK_A), 'ticketmatic:cultuurhuis:1001')
            ),
            $this->repository->findByIdentifier('ticketmatic:cultuurhuis:1001')
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
        $original = new TicketingRegistration($this->eventId, new Url(self::LINK_A), 'ticketmatic:cultuurhuis:2002');
        $copy = new TicketingRegistration($this->otherEventId, new Url(self::LINK_A), 'ticketmatic:cultuurhuis:2002');
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
        $this->repository->add(new TicketingRegistration($this->eventId, new Url(self::LINK_A), 'ticketmatic:cultuurhuis:1001'));
        $unregistered = new TicketingRegistration($this->eventId, new Url(self::LINK_B));
        $this->repository->add($unregistered);

        $this->assertEquals(new TicketingRegistrations($unregistered), $this->repository->findWithoutIdentifier());
    }
}
