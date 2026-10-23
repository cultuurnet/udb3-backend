<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Events\ReservationLinkAdded;
use CultuurNet\UDB3\Event\Events\ReservationLinkRegistered;
use CultuurNet\UDB3\Event\Events\ReservationLinkRemoved;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistration;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrationRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TicketingRegistrationsProjectorTest extends TestCase
{
    private const EVENT_ID = '9a9ec5fe-1e42-4c38-9b6f-60e7a2b3a1c1';

    private TicketingRegistrationRepository&MockObject $repository;
    private TicketingRegistrationsProjector $projector;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TicketingRegistrationRepository::class);
        $this->projector = new TicketingRegistrationsProjector(
            $this->repository,
            ['leuven-school-met-cultuur.ticketmatic.com']
        );
    }

    /**
     * @test
     */
    public function it_adds_a_registration_for_a_link_on_a_ticketing_host(): void
    {
        $link = new Url('https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345');

        $this->repository->expects($this->once())
            ->method('add')
            ->with(new TicketingRegistration(new Uuid(self::EVENT_ID), $link));

        $this->projector->handle($this->domainMessage(new ReservationLinkAdded(self::EVENT_ID, $link)));
    }

    /**
     * @test
     */
    public function it_matches_the_host_case_insensitive(): void
    {
        $link = new Url('https://Leuven-School-Met-Cultuur.Ticketmatic.com/30cc/register/12345');

        $this->repository->expects($this->once())->method('add');

        $this->projector->handle($this->domainMessage(new ReservationLinkAdded(self::EVENT_ID, $link)));
    }

    /**
     * @test
     */
    public function it_ignores_links_on_other_hosts(): void
    {
        $this->repository->expects($this->never())->method('add');

        $this->projector->handle(
            $this->domainMessage(new ReservationLinkAdded(self::EVENT_ID, new Url('https://www.example.com/tickets')))
        );
    }

    /**
     * @test
     */
    public function it_ignores_links_that_do_not_fit_in_the_table(): void
    {
        $this->repository->expects($this->never())->method('add');

        $this->projector->handle(
            $this->domainMessage(
                new ReservationLinkAdded(
                    self::EVENT_ID,
                    new Url('https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345?' . str_repeat('a', 500))
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_ignores_links_on_the_ticketing_host_of_another_environment(): void
    {
        $this->repository->expects($this->never())->method('add');

        $this->projector->handle(
            $this->domainMessage(
                new ReservationLinkAdded(
                    self::EVENT_ID,
                    new Url('https://leuven-school-met-cultuur.test.ticketmatic.com/30cc/register/12345')
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_removes_the_registration_of_a_removed_link(): void
    {
        $link = new Url('https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345');

        $this->repository->expects($this->once())
            ->method('remove')
            ->with(new Uuid(self::EVENT_ID), $link);

        $this->projector->handle($this->domainMessage(new ReservationLinkRemoved(self::EVENT_ID, $link)));
    }

    /**
     * @test
     */
    public function it_removes_a_link_on_a_host_that_is_no_longer_configured(): void
    {
        $link = new Url('https://www.example.com/tickets');

        $this->repository->expects($this->once())
            ->method('remove')
            ->with(new Uuid(self::EVENT_ID), $link);

        $this->projector->handle($this->domainMessage(new ReservationLinkRemoved(self::EVENT_ID, $link)));
    }

    /**
     * @test
     */
    public function it_sets_the_identifier_of_a_registered_link(): void
    {
        $link = new Url('https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345');

        $this->repository->expects($this->once())
            ->method('setIdentifier')
            ->with(new Uuid(self::EVENT_ID), $link, 'ticketmatic:30cc:12345');

        $this->projector->handle(
            $this->domainMessage(new ReservationLinkRegistered(self::EVENT_ID, $link, 'ticketmatic:30cc:12345'))
        );
    }

    private function domainMessage(object $payload): DomainMessage
    {
        return new DomainMessage(self::EVENT_ID, 1, new Metadata(), $payload, DateTime::now());
    }
}
