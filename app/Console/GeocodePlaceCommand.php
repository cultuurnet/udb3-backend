<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeocodePlaceCommand extends AbstractGeocodeCommand
{
    /**
     * @var DocumentRepository
     */
    private $documentRepository;

    public function __construct(CommandBusInterface $commandBus, Connection $connection, DocumentRepository $documentRepository)
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
    protected function getAllItemsSQLFile()
    {
        return __DIR__ . '/SQL/get_all_places.sql';
    }

    /**
     * @inheritdoc
     */
    protected function getOutdatedItemsSQLFile()
    {
        return __DIR__ . '/SQL/get_places_with_missing_or_outdated_coordinates.sql';
    }

    /**
     * @inheritdoc
     */
    protected function dispatchGeocodingCommand($placeId, OutputInterface $output)
    {
        try {
            $document = $this->documentRepository->get($placeId);
        } catch (DocumentGoneException $e) {
            $document = null;
        }

        if (is_null($document)) {
            $output->writeln("Skipping {$placeId}. (Could not find JSON-LD in local repository.)");
            return;
        }

        $jsonLd = json_decode($document->getRawBody(), true);

        $mainLanguage = isset($jsonLd->mainLanguage) ? $jsonLd->mainLanguage : 'nl';

        if (!isset($jsonLd['address'])) {
            $output->writeln("Skipping {$placeId}. (JSON-LD does not contain an address.)");
            return;
        }

        if (!isset($jsonLd['address'][$mainLanguage])) {
            $output->writeln("Skipping {$placeId}. (JSON-LD does not contain an address for {$mainLanguage}.)");
            return;
        }

        try {
            $address = Address::deserialize($jsonLd['address'][$mainLanguage]);
        } catch (\Exception $e) {
            $output->writeln("Skipping {$placeId}. (JSON-LD address for {$mainLanguage} could not be parsed.)");
            return;
        }

        $this->commandBus->dispatch(
            new UpdateGeoCoordinatesFromAddress($placeId, $address)
        );

        $output->writeln("Dispatched geocode command for place {$placeId}.");
    }
}
