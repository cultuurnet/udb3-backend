<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;
use Symfony\Component\Console\Output\OutputInterface;

class GeocodePlaceCommand extends AbstractGeocodeCommand
{
    public function configure(): void
    {
        parent::configure();
        $this
            ->setName('place:geocode')
            ->setDescription('Geocode places with missing or outdated coordinates.');
    }

    protected function getQueryForMissingCoordinates(): string
    {
        return 'NOT(_exists_:geo OR workflowStatus:DELETED OR workflowStatus:REJECTED)';
    }

    protected function dispatchGeocodingCommand(string $placeId, OutputInterface $output): void
    {
        $document = $this->getDocument($placeId);
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
