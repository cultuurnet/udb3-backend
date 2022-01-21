<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Organizer\Commands\UpdateGeoCoordinatesFromAddress;
use Symfony\Component\Console\Output\OutputInterface;

class GeocodeOrganizerCommand extends AbstractGeocodeCommand
{
    public function configure(): void
    {
        parent::configure();
        $this
            ->setName('organizer:geocode')
            ->setDescription('Geocode organizers with missing or outdated coordinates.');
    }

    protected function getQueryForMissingCoordinates(): string
    {
        return 'NOT(_exists_:geo OR workflowStatus:DELETED OR workflowStatus:REJECTED)';
    }

    protected function dispatchGeocodingCommand(string $organizerId, OutputInterface $output): void
    {
        $document = $this->getDocument($organizerId);

        if (is_null($document)) {
            $output->writeln("Skipping {$organizerId}. (Could not find JSON-LD in local repository.)");
            return;
        }

        $jsonLd = Json::decodeAssociatively($document->getRawBody());

        $mainLanguage = $jsonLd->mainLanguage ?? 'nl';

        if (!isset($jsonLd['address'])) {
            $output->writeln("Skipping {$organizerId}. (JSON-LD does not contain an address.)");
            return;
        }

        if (!isset($jsonLd['address'][$mainLanguage])) {
            $output->writeln("Skipping {$organizerId}. (JSON-LD does not contain an address for {$mainLanguage}.)");
            return;
        }

        try {
            $address = Address::deserialize($jsonLd['address'][$mainLanguage]);
        } catch (\Exception $e) {
            $output->writeln("Skipping {$organizerId}. (JSON-LD address for {$mainLanguage} could not be parsed.)");
            return;
        }

        $this->commandBus->dispatch(
            new UpdateGeoCoordinatesFromAddress($organizerId, $address)
        );

        $output->writeln("Dispatched geocode command for organizer {$organizerId}.");
    }
}
