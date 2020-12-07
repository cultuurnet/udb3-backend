<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeocodeEventCommand extends AbstractGeocodeCommand
{
    /**
     * @inheritdoc
     */
    public function configure(): void
    {
        $this
            ->setName('event:geocode')
            ->setDescription('Geocode events with missing or outdated coordinates.')
            ->addOption(
                'cdbid',
                null,
                InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL,
                'Fixed list of cdbids of the events to geocode.'
            );
    }

    protected function getQueryForMissingCoordinates(): string
    {
        // Only geo-code events without location id. Events with a location id can only be geo-coded by geo-coding the
        // linked place.
        return 'NOT(_exists_:geo) AND NOT(_exists_:location.id)';
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
