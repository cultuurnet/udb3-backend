<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\ReadModel\DocumentRepositoryInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeocodeEventCommand extends AbstractGeocodeCommand
{
    /**
     * @var DocumentRepositoryInterface
     */
    private $documentRepository;

    public function __construct(CommandBusInterface $commandBus, Connection $connection, DocumentRepositoryInterface $documentRepository)
    {
        parent::__construct($commandBus, $connection);
        $this->documentRepository = $documentRepository;
    }

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
    protected function getAllItemsSQLFile()
    {
        return __DIR__ . '/SQL/get_all_events.sql';
    }

    /**
     * @inheritdoc
     */
    protected function getOutdatedItemsSQLFile()
    {
        return __DIR__ . '/SQL/get_events_with_missing_or_outdated_coordinates.sql';
    }

    /**
     * @inheritdoc
     */
    protected function dispatchGeocodingCommand($eventId, OutputInterface $output)
    {

        try {
            $document = $this->documentRepository->get($eventId);
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

        $this->commandBus->dispatch(
            new UpdateGeoCoordinatesFromAddress($eventId, $address)
        );

        $output->writeln("Dispatched geocode command for event {$eventId}.");
    }
}
