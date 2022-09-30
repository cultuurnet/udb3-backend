<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use League\Csv\Reader;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCsvImportCommand extends AbstractCommand
{
    private const CSV_FILE_ARG = 'csv_file';
    private const CSV_DELIMETER_OPT = 'csv_delimiter';

    public function configure()
    {
        $this
            ->addArgument(
                self::CSV_FILE_ARG,
                InputArgument::REQUIRED,
                'Full path to the csv file to import. ' . $this->getColumnHeaders()
            )
            ->addOption(
                self::CSV_DELIMETER_OPT,
                'd',
                InputOption::VALUE_OPTIONAL,
                'Delimeter for the csv file (default is comma).',
                ','
            );
    }


    abstract public function getColumnHeaders(): string;

    /**
     * @inheritdoc
     * @throws \League\Csv\Exception
     * @see Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csvReader = Reader::createFromPath(
            $input->getArgument(self::CSV_FILE_ARG)
        );

        $csvReader->setHeaderOffset(0)
            ->setDelimiter(
                $input->getOption(self::CSV_DELIMETER_OPT)
            );

        $output->writeln('Starting import...');

        $records = $csvReader->getRecords();
        foreach ($records as $record) {
            $this->processRecord($input, $output, $record);
        }

        $output->writeln('Finished import.');

        return 0;
    }


    abstract public function processRecord(
        InputInterface $input,
        OutputInterface $output,
        array $record
    ): void;
}
