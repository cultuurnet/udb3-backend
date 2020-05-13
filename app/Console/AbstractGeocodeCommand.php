<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractGeocodeCommand extends AbstractCommand
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(CommandBusInterface $commandBus, Connection $connection)
    {
        parent::__construct($commandBus);
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cdbids = array_values($input->getOption('cdbid'));

        if ($input->getOption('all')) {
            $cdbids = $this->getAllCdbIds();
        } elseif (empty($cdbids)) {
            $cdbids = $this->getOutdatedCdbIds();
        }

        $count = count($cdbids);

        if ($count == 0) {
            $output->writeln("Could not find any items with missing or outdated coordinates.");
            return;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "This action will queue {$count} items for geocoding, continue? [y/N] ",
            true
        );

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        foreach ($cdbids as $cdbid) {
            $this->dispatchGeocodingCommand($cdbid, $output);
        }
    }

    /**
     * @param string $itemId
     * @param OutputInterface $output
     */
    abstract protected function dispatchGeocodingCommand($itemId, OutputInterface $output);

    /**
     * @return string
     */
    abstract protected function getAllItemsSQLFile();

    /**
     * @return string
     */
    abstract protected function getOutdatedItemsSQLFile();

    /**
     * @return mixed[]
     * @throws DBALException
     */
    private function getAllCdbIds()
    {
        $sql = file_get_contents($this->getAllItemsSQLFile());
        $results = $this->connection->query($sql);
        return $results->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return mixed[]
     * @throws DBALException
     */
    private function getOutdatedCdbIds()
    {
        $sql = file_get_contents($this->getOutdatedItemsSQLFile());
        $results = $this->connection->query($sql);
        return $results->fetchAll(\PDO::FETCH_COLUMN);
    }
}
