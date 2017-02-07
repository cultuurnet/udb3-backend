<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class GeocodeCommand extends AbstractSystemUserCommand
{
    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName('place:geocode')
            ->setDescription('Geocode places with missing or outdated coordinates.')
            ->addOption(
                'cdbid',
                null,
                InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL,
                'Fixed list of cdbids of the places to geocode.'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Geocode all places in the event store.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cdbids = array_values($input->getOption('cdbid'));

        if ($input->getOption('all')) {
            $cdbids = $this->getAllCdbIds();
        } elseif (empty($givenCdbIds)) {
            $cdbids = $this->getOutdatedCdbIds();
        }

        $count = count($cdbids);

        if ($count == 0) {
            $output->writeln("Could not find any places with missing or outdated coordinates.");
            return;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "This action will queue {$count} places for geocoding, continue? [y/N] ",
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $this->impersonateUDB3SystemUser();

        foreach ($cdbids as $cdbid) {
            $this->dispatchGeocodingCommand($cdbid, $output);
        }
    }

    /**
     * @return string[]
     */
    private function getAllCdbIds()
    {
        $sql = file_get_contents(__DIR__ . '/SQL/get_all_places.sql');
        $results = $this->getDBALConnection()->query($sql);
        return $results->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return string[]
     */
    private function getOutdatedCdbIds()
    {
        $sql = file_get_contents(__DIR__ . '/SQL/get_places_with_missing_or_outdated_coordinates.sql');
        $results = $this->getDBALConnection()->query($sql);
        return $results->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @param string $placeId
     * @param OutputInterface $output
     */
    private function dispatchGeocodingCommand($placeId, OutputInterface $output)
    {
        $jsonLdRepository = $this->getJsonLDRepository();

        try {
            $document = $jsonLdRepository->get($placeId);
        } catch (DocumentGoneException $e) {
            $document = null;
        }

        if (is_null($document)) {
            $output->writeln("Skipping {$placeId}. (Could not find JSON-LD in local repository.)");
            return;
        }

        $jsonLd = json_decode($document->getRawBody(), true);

        if (!isset($jsonLd['address'])) {
            $output->writeln("Skipping {$placeId}. (JSON-LD does not contain an address.)");
            return;
        }

        try {
            $address = Address::deserialize($jsonLd['address']);
        } catch (\Exception $e) {
            $output->writeln("Skipping {$placeId}. (JSON-LD address could not be parsed.)");
            return;
        }

        $this->getCommandBus()->dispatch(
            new UpdateGeoCoordinatesFromAddress($placeId, $address)
        );

        $output->writeln("Dispatched geocode command for {$placeId}.");
    }

    /**
     * @return Connection
     */
    private function getDBALConnection()
    {
        $app = $this->getSilexApplication();
        return $app['dbal_connection'];
    }

    /**
     * @return DocumentRepositoryInterface
     */
    private function getJsonLDRepository()
    {
        $app = $this->getSilexApplication();
        return $app['place_jsonld_repository'];
    }

    /**
     * @return CommandBusInterface
     */
    private function getCommandBus()
    {
        $app = $this->getSilexApplication();
        return $app['event_command_bus'];
    }
}
