<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
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
        return '_exists_:address NOT(_exists_:geo OR workflowStatus:DELETED OR workflowStatus:REJECTED)';
    }

    protected function dispatchGeocodingCommand(string $organizerId, OutputInterface $output): void
    {
        $document = $this->getDocument($organizerId);

        if (is_null($document)) {
            $output->writeln("Skipping {$organizerId}. (Could not find JSON-LD in local repository.)");
            return;
        }

        $jsonLd = Json::decodeAssociatively($document->getRawBody());

        $addressLanguage = $jsonLd->mainLanguage ?? 'nl';

        if (!isset($jsonLd['address'][$addressLanguage])) {
            // Some organizers have an address in another language then the main language or `nl`
            $addressLanguage = array_key_first($jsonLd['address']);
            if ($addressLanguage === null) {
                $output->writeln("Skipping {$organizerId}. (JSON-LD does not contain an address for {$addressLanguage}.)");
                return;
            }
        }

        try {
            $address = (new AddressDenormalizer())->denormalize($jsonLd['address'][$addressLanguage], Address::class);
        } catch (\Exception $e) {
            $output->writeln("Skipping {$organizerId}. (JSON-LD address for {$addressLanguage} could not be parsed.)");
            return;
        }

        $output->writeln("Dispatching geocode command for organizer {$organizerId}.");

        $this->commandBus->dispatch(
            new UpdateGeoCoordinatesFromAddress($organizerId, $address)
        );
    }
}
