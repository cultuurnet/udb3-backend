<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
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
        return '_exists_:address NOT(_exists_:geo OR workflowStatus:DELETED OR workflowStatus:REJECTED)';
    }

    protected function dispatchGeocodingCommand(string $placeId, OutputInterface $output): void
    {
        $document = $this->getDocument($placeId);
        if (is_null($document)) {
            $output->writeln("Skipping {$placeId}. (Could not find JSON-LD in local repository.)");
            return;
        }

        $jsonLd = Json::decodeAssociatively($document->getRawBody());

        if (!isset($jsonLd['address'])) {
            $output->writeln("Skipping {$placeId}. (JSON-LD does not contain an address.)");
            return;
        }

        $addressLanguage = $jsonLd->mainLanguage ?? 'nl';

        if (!isset($jsonLd['address'][$addressLanguage])) {
            // Some places have an address in another language then the main language or `nl`
            $addressLanguage = array_key_first($jsonLd['address']);
            if ($addressLanguage === null) {
                $output->writeln("Skipping {$placeId}. (JSON-LD does not contain an address for {$addressLanguage}.)");
                return;
            }
        }

        try {
            $address = (new AddressDenormalizer())->denormalize($jsonLd['address'][$addressLanguage], Address::class);
        } catch (\Exception $e) {
            $output->writeln("Skipping {$placeId}. (JSON-LD address for {$addressLanguage} could not be parsed.)");
            return;
        }

        $output->writeln("Dispatching geocode command for place {$placeId}.");

        $this->commandBus->dispatch(
            new UpdateGeoCoordinatesFromAddress($placeId, $address)
        );
    }
}
