<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Place\Events\MarkedAsDuplicate;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ValueObjects\Identity\UUID;

class DispatchMarkedAsDuplicateEventCommand extends AbstractCommand
{
    private const DUPLICATE_PLACE_ID_ARGUMENT = 'duplicate_place_id';
    private const CANONICAL_PLACE_ID_ARGUMENT = 'canonical_place_id';

    public function configure()
    {
        $this->setName('place:mark-as-duplicate:redispatch-event');
        $this->setDescription('Re-dispatch the MarkedAsDuplicate event to trigger related process managers');
        $this->addArgument(self::DUPLICATE_PLACE_ID_ARGUMENT, InputArgument::REQUIRED, 'uuid of the duplicate place');
        $this->addArgument(self::CANONICAL_PLACE_ID_ARGUMENT, InputArgument::REQUIRED, 'uuid of the canonical place');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
            $this->getEventBus()->publish(
                new DomainEventStream(
                    [
                        DomainMessage::recordNow(
                            UUID::generateAsString(),
                            0,
                            Metadata::deserialize([]),
                            new MarkedAsDuplicate(
                                $input->getArgument(self::DUPLICATE_PLACE_ID_ARGUMENT),
                                $input->getArgument(self::CANONICAL_PLACE_ID_ARGUMENT)
                            )
                        ),
                    ]
                )
            );
            $output->writeln('Successfully re-dispatched MarkedAsDuplicate event');
    }

    /**
     * @return EventBusInterface
     */
    protected function getEventBus()
    {
        $app = $this->getSilexApplication();
        return $app['event_bus'];
    }
}
