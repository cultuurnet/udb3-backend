<?php

namespace CultuurNet\UDB3\Silex\Console;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractGeocodeCommand extends AbstractCommand
{
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
            false
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
     * @return string[]
     */
    private function getAllCdbIds()
    {
        $sql = file_get_contents($this->getAllItemsSQLFile());
        $results = $this->getDBALConnection()->query($sql);
        return $results->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return string[]
     */
    private function getOutdatedCdbIds()
    {
        $sql = file_get_contents($this->getOutdatedItemsSQLFile());
        $results = $this->getDBALConnection()->query($sql);
        return $results->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return Connection
     */
    private function getDBALConnection()
    {
        $app = $this->getSilexApplication();
        return $app['dbal_connection'];
    }
}
