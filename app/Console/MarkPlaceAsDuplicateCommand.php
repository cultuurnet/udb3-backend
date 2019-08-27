<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Place\CannotMarkPlaceAsCanonical;
use CultuurNet\UDB3\Place\CannotMarkPlaceAsDuplicate;
use CultuurNet\UDB3\Place\Commands\MarkAsDuplicate;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MarkPlaceAsDuplicateCommand extends AbstractCommand
{
    private const DUPLICATE_PLACE_ID_ARGUMENT = 'duplicate_place_id';
    private const CANONICAL_PLACE_ID_ARGUMENT = 'canonical_place_id';

    public function configure()
    {
        $this->setName('place:mark-as-duplicate');
        $this->setDescription('Marks a Place as duplicate of another one, implicitly making that a canonical');
        $this->addArgument(self::DUPLICATE_PLACE_ID_ARGUMENT, InputArgument::REQUIRED, 'uuid of the duplicate place');
        $this->addArgument(self::CANONICAL_PLACE_ID_ARGUMENT, InputArgument::REQUIRED, 'uuid of the canonical place');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->getCommandBus()->dispatch(
                new MarkAsDuplicate(
                    $input->getArgument(self::DUPLICATE_PLACE_ID_ARGUMENT),
                    $input->getArgument(self::CANONICAL_PLACE_ID_ARGUMENT)
                )
            );
            $output->writeln('Successfully marked place as duplicate');
        } catch (CannotMarkPlaceAsCanonical | CannotMarkPlaceAsDuplicate $e) {
            $output->writeln($e->getMessage());
        }
    }
}
