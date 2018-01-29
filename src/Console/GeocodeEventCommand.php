<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class GeocodeEventCommand extends AbstractCommand
{
    /**
     * @inheritdoc
     */
    public function configure()
    {
        $this
            ->setName('event:geocode')
            ->setDescription('Geocode events with missing or outdated coordinates.')
            ->addOption(
                'cdbid',
                null,
                InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL,
                'Fixed list of cdbids of the events to geocode.'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Geocode all events in the event store.'
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
        } elseif (empty($cdbids)) {
            $cdbids = $this->getOutdatedCdbIds();
        }

        $count = count($cdbids);

        if ($count == 0) {
            $output->writeln("Could not find any events with missing or outdated coordinates.");
            return;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "This action will queue {$count} events for geocoding, continue? [y/N] ",
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
     * @return string[]
     */
    private function getAllCdbIds()
    {
        $sql = file_get_contents(__DIR__ . '/SQL/get_all_events.sql');
        $results = $this->getDBALConnection()->query($sql);
        return $results->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @return string[]
     */
    private function getOutdatedCdbIds()
    {
        $sql = file_get_contents(__DIR__ . '/SQL/get_events_with_missing_or_outdated_coordinates.sql');
        $results = $this->getDBALConnection()->query($sql);
        return $results->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @param string $eventId
     * @param OutputInterface $output
     */
    private function dispatchGeocodingCommand($eventId, OutputInterface $output)
    {
        $jsonLdRepository = $this->getJsonLDRepository();

        try {
            $document = $jsonLdRepository->get($eventId);
        } catch (DocumentGoneException $e) {
            $document = null;
        }

        if (is_null($document)) {
            $output->writeln("Skipping {$eventId}. (Could not find JSON-LD in local repository.)");
            return;
        }

        $jsonLd = json_decode($document->getRawBody(), true);

        $mainLanguage = isset($jsonLd->mainLanguage) ? $jsonLd->mainLanguage : 'nl';

        if (!isset($jsonLd['location'])) {
            $output->writeln("Skipping {$eventId}. (JSON-LD does not contain a location.)");
            return;
        }
        $location = $jsonLd['location'];

        if (!isset($location['address'])) {
            $output->writeln("Skipping {$eventId}. (JSON-LD does not contain an address.)");
            return;
        }

        if (isset($location['address'][$mainLanguage])) {
            try {
                $address = Address::deserialize($location['address'][$mainLanguage]);
            } catch (\Exception $e) {
                $output->writeln("Skipping {$eventId}. (JSON-LD address for {$mainLanguage} could not be parsed.)");
                return;
            }
        } else {
            // The code to importLocation from cdbxml doesn't take into account main language.
            // So a fallback is provided to handle untranslated addresses.
            try {
                $address = Address::deserialize($location['address']);
            } catch (\Exception $e) {
                $output->writeln("Skipping {$eventId}. (JSON-LD address could not be parsed.");
                return;
            }
        }

        $this->getCommandBus()->dispatch(
            new UpdateGeoCoordinatesFromAddress($eventId, $address)
        );

        $output->writeln("Dispatched geocode command for {$eventId}.");
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
        return $app['event_jsonld_repository'];
    }
}
