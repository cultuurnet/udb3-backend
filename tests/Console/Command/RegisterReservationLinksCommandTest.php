<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\RegisterReservationLink;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistration;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrationRepository;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class RegisterReservationLinksCommandTest extends TestCase
{
    private const EVENT_ID = '9a9ec5fe-1e42-4c38-9b6f-60e7a2b3a1c1';
    private const OTHER_EVENT_ID = '2f1c4e6a-8d4b-4f0e-9d2a-7c3b5e1f0a92';
    private const LINK_A = 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/1001';
    private const LINK_B = 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/1002';

    private TraceableCommandBus $commandBus;

    private TicketingRegistrationRepository&MockObject $repository;

    private QuestionHelper&MockObject $questionHelper;

    private RegisterReservationLinksCommand $command;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->repository = $this->createMock(TicketingRegistrationRepository::class);

        $this->command = new RegisterReservationLinksCommand($this->commandBus, $this->repository);

        $this->questionHelper = $this->createMock(QuestionHelper::class);
        $this->command->setHelperSet(new HelperSet(['question' => $this->questionHelper]));
    }

    /**
     * @test
     */
    public function it_registers_every_link_without_identifier_again(): void
    {
        $this->repository->method('findWithoutIdentifier')->willReturn(
            new TicketingRegistrations(
                new TicketingRegistration(new Uuid(self::EVENT_ID), new Url(self::LINK_A)),
                new TicketingRegistration(new Uuid(self::OTHER_EVENT_ID), new Url(self::LINK_B))
            )
        );
        $this->questionHelper->method('ask')->willReturn(true);

        $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            [
                new RegisterReservationLink(self::EVENT_ID, new Url(self::LINK_A)),
                new RegisterReservationLink(self::OTHER_EVENT_ID, new Url(self::LINK_B)),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_registers_every_link_of_the_given_events_again(): void
    {
        $this->repository->expects($this->never())->method('findWithoutIdentifier');
        $this->repository->method('findByEvent')->willReturnCallback(
            fn (Uuid $eventId) => $eventId->toString() === self::EVENT_ID
                ? new TicketingRegistrations(
                    new TicketingRegistration(new Uuid(self::EVENT_ID), new Url(self::LINK_A), 'ticketmatic:30cc:1001'),
                    new TicketingRegistration(new Uuid(self::EVENT_ID), new Url(self::LINK_B))
                )
                : new TicketingRegistrations()
        );

        $this->command->run(
            new ArrayInput(['--id' => [self::EVENT_ID, self::OTHER_EVENT_ID], '--force' => true]),
            new BufferedOutput()
        );

        $this->assertEquals(
            [
                new RegisterReservationLink(self::EVENT_ID, new Url(self::LINK_A)),
                new RegisterReservationLink(self::EVENT_ID, new Url(self::LINK_B)),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_registers_nothing_when_the_confirmation_is_declined(): void
    {
        $this->repository->method('findWithoutIdentifier')->willReturn(
            new TicketingRegistrations(new TicketingRegistration(new Uuid(self::EVENT_ID), new Url(self::LINK_A)))
        );
        $this->questionHelper->method('ask')->willReturn(false);

        $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_does_not_ask_for_confirmation_when_forced(): void
    {
        $this->repository->method('findWithoutIdentifier')->willReturn(
            new TicketingRegistrations(new TicketingRegistration(new Uuid(self::EVENT_ID), new Url(self::LINK_A)))
        );
        $this->questionHelper->expects($this->never())->method('ask');

        $this->command->run(new ArrayInput(['--force' => true]), new BufferedOutput());

        $this->assertCount(1, $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_tells_when_there_is_nothing_to_register(): void
    {
        $this->repository->method('findWithoutIdentifier')->willReturn(new TicketingRegistrations());
        $output = new BufferedOutput();

        $this->command->run(new ArrayInput([]), $output);

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
        $this->assertStringContainsString('There are no reservation links to register.', $output->fetch());
    }
}
