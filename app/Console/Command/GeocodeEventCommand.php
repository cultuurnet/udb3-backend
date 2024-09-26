<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Event\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
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

        $jsonLd = Json::decodeAssociatively($document->getRawBody());

        $mainLanguage = $jsonLd->mainLanguage ?? 'nl';

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
                $address = (new AddressDenormalizer())->denormalize($location['address'][$mainLanguage], Address::class);
            } catch (\Exception $e) {
                $output->writeln("Skipping {$eventId}. (JSON-LD address for {$mainLanguage} could not be parsed.)");
                return;
            }
        } else {
            // The code to importLocation from cdbxml doesn't take into account main language.
            // So a fallback is provided to handle untranslated addresses.
            try {
                $address = (new AddressDenormalizer())->denormalize($location['address'], Address::class);
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
