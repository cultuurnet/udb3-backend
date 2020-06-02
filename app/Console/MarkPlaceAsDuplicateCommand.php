<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Place\CannotMarkPlaceAsCanonical;
use CultuurNet\UDB3\Place\CannotMarkPlaceAsDuplicate;
use CultuurNet\UDB3\Place\Commands\MarkAsDuplicate;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class MarkPlaceAsDuplicateCommand extends AbstractCommand
{
    private const DUPLICATE_PLACE_ID_ARGUMENT = 'duplicate_place_id';
    private const CANONICAL_PLACE_ID_ARGUMENT = 'canonical_place_id';

    /**
     * @var EventListenerInterface
     */
    private $processManager;

    public function __construct(CommandBusInterface $commandBus, EventListenerInterface $processManager)
    {
        parent::__construct($commandBus);
        $this->processManager = $processManager;
    }


    public function configure()
    {
        $this->setName('place:mark-as-duplicate');
        $this->setDescription('Marks a Place as duplicate of another one, implicitly making that a canonical');
        $this->addArgument(self::DUPLICATE_PLACE_ID_ARGUMENT, InputArgument::REQUIRED, 'uuid of the duplicate place');
        $this->addArgument(self::CANONICAL_PLACE_ID_ARGUMENT, InputArgument::REQUIRED, 'uuid of the canonical place');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);
        $this->processManager->setLogger($logger);

        try {
            $this->commandBus->dispatch(
                new MarkAsDuplicate(
                    $input->getArgument(self::DUPLICATE_PLACE_ID_ARGUMENT),
                    $input->getArgument(self::CANONICAL_PLACE_ID_ARGUMENT)
                )
            );
            $logger->info('Successfully marked place as duplicate');
        } catch (CannotMarkPlaceAsCanonical | CannotMarkPlaceAsDuplicate $e) {
            $logger->error($e->getMessage());
        }
    }
}
