<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use Symfony\Component\Console\Output\OutputInterface;

class GeocodeEventCommand extends AbstractGeocodeCommand
{
    public function configure(): void
    {
        parent::configure();
        $this
            ->setName('event:geocode')
            ->setDescription('Geocode events with missing or outdated coordinates.');
    }

    protected function getQueryForMissingCoordinates(): string
    {
        // Only geo-code events without location id. Events with a location id can only be geo-coded by geo-coding the
        // linked place.
        return '_exists:address NOT(_exists_:geo OR _exists_:location.id OR workflowStatus:DELETED OR workflowStatus:REJECTED)';
    }

    protected function dispatchGeocodingCommand(string $eventId, OutputInterface $output): void
    {
        $document = $this->getDocument($eventId);
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

        if (isset($location['@id'])) {
            $output->writeln(
                "Skipping {$eventId}. (JSON-LD contains a location with an id. Geocode the linked place instead.)"
            );
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

        $output->writeln("Dispatching geocode command for event {$eventId}.");

        $this->commandBus->dispatch(
            new UpdateGeoCoordinatesFromAddress($eventId, $address)
        );
    }
}
