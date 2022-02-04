<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Place\Events\MarkedAsDuplicate;
use Psr\Log\LoggerAwareInterface;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class DispatchMarkedAsDuplicateEventCommand extends AbstractCommand
{
    private const DUPLICATE_PLACE_ID_ARGUMENT = 'duplicate_place_id';
    private const CANONICAL_PLACE_ID_ARGUMENT = 'canonical_place_id';

    /**
     * @var EventListener
     */
    private $processManager;

    /**
     * @var EventBus
     */
    private $eventBus;

    private UuidFactoryInterface $uuidFactory;

    public function __construct(
        CommandBus $commandBus,
        EventListener $processManager,
        EventBus $eventBus,
        UuidFactoryInterface $uuidFactory
    ) {
        parent::__construct($commandBus);
        $this->processManager = $processManager;
        $this->eventBus = $eventBus;
        $this->uuidFactory = $uuidFactory;
    }


    public function configure()
    {
        $this->setName('place:mark-as-duplicate:redispatch-event');
        $this->setDescription('Re-dispatch the MarkedAsDuplicate event to trigger related process managers');
        $this->addArgument(self::DUPLICATE_PLACE_ID_ARGUMENT, InputArgument::REQUIRED, 'uuid of the duplicate place');
        $this->addArgument(self::CANONICAL_PLACE_ID_ARGUMENT, InputArgument::REQUIRED, 'uuid of the canonical place');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        if ($this->processManager instanceof LoggerAwareInterface) {
            $this->processManager->setLogger($logger);
        }

        $this->eventBus->publish(
            new DomainEventStream(
                [
                    DomainMessage::recordNow(
                        $this->uuidFactory->uuid4()->toString(),
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
        $logger->info('Successfully re-dispatched MarkedAsDuplicate event');

        return 0;
    }
}
