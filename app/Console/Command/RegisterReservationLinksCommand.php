<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\RegisterReservationLink;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistration;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrationRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Registers reservation links again at their ticket sales provider, for when the first lookup failed: the provider
 * was down, or the venue hadn't opened the event yet.
 */
final class RegisterReservationLinksCommand extends AbstractCommand
{
    public function __construct(
        CommandBus $commandBus,
        private readonly TicketingRegistrationRepository $ticketingRegistrationRepository
    ) {
        parent::__construct($commandBus);
    }

    protected function configure(): void
    {
        $this
            ->setName('event:register-reservation-links')
            ->setDescription('Register reservation links again at their ticket sales provider.')
            ->addOption(
                'id',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Id of an event to register all reservation links of. Without it, every reservation link without identifier is registered again.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Skip confirmation.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $registrations = $this->findRegistrations($input->getOption('id'));
        $count = count($registrations);

        if ($count === 0) {
            $output->writeln('There are no reservation links to register.');
            return 0;
        }

        if (!$this->askConfirmation($input, $output, $count)) {
            return 0;
        }

        foreach ($registrations as $registration) {
            $output->writeln(
                sprintf(
                    'Registering %s of event %s.',
                    $registration->getReservationLink()->toString(),
                    $registration->getEventId()->toString()
                )
            );

            $this->commandBus->dispatch(
                new RegisterReservationLink(
                    $registration->getEventId()->toString(),
                    $registration->getReservationLink()
                )
            );
        }

        return 0;
    }

    /**
     * @param string[] $eventIds
     * @return TicketingRegistration[]
     */
    private function findRegistrations(array $eventIds): array
    {
        if (empty($eventIds)) {
            return $this->ticketingRegistrationRepository->findWithoutIdentifier()->toArray();
        }

        $registrations = [];
        foreach ($eventIds as $eventId) {
            $registrations = [
                ...$registrations,
                ...$this->ticketingRegistrationRepository->findByEvent(new Uuid($eventId))->toArray(),
            ];
        }

        return $registrations;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will register {$count} reservation links again, continue? [y/N] ",
                    false
                )
            );
    }
}
