<?php

namespace CultuurNet\UDB3\Silex\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSavedSearchesCommand extends AbstractCommand
{
    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName('savedSearches:import')
            ->setDescription('Import saved searches from the given CSV file')
            ->addArgument(
                'csv_file',
                InputArgument::REQUIRED,
                'Full path to the csv file to import'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
