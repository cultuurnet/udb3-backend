<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Commands\RegisterReservationLink;
use CultuurNet\UDB3\Event\Events\ReservationLinkAdded;
use CultuurNet\UDB3\Event\Events\ReservationLinkRemoved;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RegisterAddedReservationLinksTest extends TestCase
{
    private const EVENT_ID = '9a9ec5fe-1e42-4c38-9b6f-60e7a2b3a1c1';

    private CommandBus&MockObject $commandBus;

    private RegisterAddedReservationLinks $registerAddedReservationLinks;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->registerAddedReservationLinks = new RegisterAddedReservationLinks(
            $this->commandBus,
            ['leuven-school-met-cultuur.ticketmatic.com']
        );
    }

    /**
     * @test
     */
    public function it_registers_a_link_on_a_ticketing_host(): void
    {
        $link = new Url('https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345');

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new RegisterReservationLink(self::EVENT_ID, $link));

        $this->registerAddedReservationLinks->handle($this->domainMessage(new ReservationLinkAdded(self::EVENT_ID, $link)));
    }

    /**
     * @test
     */
    public function it_ignores_links_on_other_hosts(): void
    {
        $this->commandBus->expects($this->never())->method('dispatch');

        $this->registerAddedReservationLinks->handle(
            $this->domainMessage(new ReservationLinkAdded(self::EVENT_ID, new Url('https://www.example.com/tickets')))
        );
    }

    /**
     * @test
     */
    public function it_ignores_removed_links(): void
    {
        $this->commandBus->expects($this->never())->method('dispatch');

        $this->registerAddedReservationLinks->handle(
            $this->domainMessage(
                new ReservationLinkRemoved(
                    self::EVENT_ID,
                    new Url('https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345')
                )
            )
        );
    }

    private function domainMessage(object $payload): DomainMessage
    {
        return new DomainMessage(self::EVENT_ID, 1, new Metadata(), $payload, DateTime::now());
    }
}
