<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddLabel extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('label:add-label')
            ->setDescription('Add labels on items from a search query.')
            ->addArgument(
                'itemType',
                InputOption::VALUE_REQUIRED,
                'The itemType for which you wish to search.'
            )
            ->addArgument(
                'query',
                InputOption::VALUE_REQUIRED,
                'The query for which you wish to add the label'
            )
            ->addArgument(
                'label',
                InputOption::VALUE_REQUIRED,
                'The label that you wish to add.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $itemType = new ItemType($input->getArgument('itemType'));

        return 0;
    }
}
