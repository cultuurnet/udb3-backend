<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\CommandHandling\CommandBus;
use CultureFeed_Cdb_Data_Address;
use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactoryInterface;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Geography\AddressDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Offer\AbstractGeoCoordinatesProcessManager;
use CultuurNet\UDB3\Place\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Log\LoggerInterface;

class GeoCoordinatesProcessManager extends AbstractGeoCoordinatesProcessManager
{
    private DocumentRepository $placeRepository;

    public function __construct(
        CommandBus $commandBus,
        CultureFeedAddressFactoryInterface $addressFactory,
        LoggerInterface $logger,
        DocumentRepository $placeRepository
    ) {
        parent::__construct($commandBus, $addressFactory, $logger);

        $this->placeRepository = $placeRepository;
    }

    protected function getEventHandlers(): array
    {
        return [
            PlaceCreated::class => 'handlePlaceCreated',
            MajorInfoUpdated::class => 'handleMajorInfoUpdated',
            AddressUpdated::class => 'handleAddressUpdated',
            PlaceImportedFromUDB2::class => 'handleActorImportedFromUDB2',
            PlaceUpdatedFromUDB2::class => 'handleActorImportedFromUDB2',
            TitleUpdated::class => 'handleTitleUpdated',
        ];
    }

    protected function handlePlaceCreated(PlaceCreated $placeCreated): void
    {
        $command = new UpdateGeoCoordinatesFromAddress(
            $placeCreated->getPlaceId(),
            $placeCreated->getAddress()->toUdb3ModelAddress()
        );

        $this->commandBus->dispatch($command);
    }

    protected function handleMajorInfoUpdated(MajorInfoUpdated $majorInfoUpdated): void
    {
        // We don't know if the address has actually been updated because
        // MajorInfoUpdated is too coarse, but if we use the cached geocoding
        // service we won't be wasting much resources when using a naive
        // approach like this.
        $command = new UpdateGeoCoordinatesFromAddress(
            $majorInfoUpdated->getPlaceId(),
            $majorInfoUpdated->getAddress()->toUdb3ModelAddress()
        );

        $this->commandBus->dispatch($command);
    }

    protected function handleAddressUpdated(AddressUpdated $addressUpdated): void
    {
        $command = new UpdateGeoCoordinatesFromAddress(
            $addressUpdated->getPlaceId(),
            $addressUpdated->getAddress()->toUdb3ModelAddress()
        );

        $this->commandBus->dispatch($command);
    }

    /**
     * @throws \CultureFeed_Cdb_ParseException
     */
    protected function handleActorImportedFromUDB2(ActorImportedFromUDB2 $actorImportedFromUDB2): void
    {
        $actor = ActorItemFactory::createActorFromCdbXml(
            $actorImportedFromUDB2->getCdbXmlNamespaceUri(),
            $actorImportedFromUDB2->getCdbXml()
        );

        $contactInfo = $actor->getContactInfo();

        // Do nothing if no contact info is found.
        if (!$contactInfo) {
            return;
        }

        // Get all physical locations from the list of addresses.
        $addresses = array_map(
            function (CultureFeed_Cdb_Data_Address $address) {
                return $address->getPhysicalAddress();
            },
            $contactInfo->getAddresses()
        );

        // Filter out addresses without physical location.
        $addresses = array_filter($addresses);

        // Do nothing if no address is found.
        if (empty($addresses)) {
            return;
        }

        /* @var \CultureFeed_Cdb_Data_Address_PhysicalAddress $cdbAddress */
        $cdbAddress = $addresses[0];

        try {
            // Convert the cdbxml address to a udb3 address.
            $address = $this->addressFactory->fromCdbAddress($cdbAddress);
        } catch (\InvalidArgumentException $e) {
            // If conversion failed, log an error and do nothing.
            $this->logger->error(
                'Could not convert a cdbxml address to a udb3 address for geocoding.',
                [
                    'placeId' => $actorImportedFromUDB2->getActorId(),
                    'error' => $e->getMessage(),
                ]
            );
            return;
        }

        // We don't know if the address has actually been updated because
        // ActorImportedFromUDB2 is too coarse, but if we use the cached
        // geocoding service we won't be wasting much resources when using
        // a naive approach like this.
        $command = new UpdateGeoCoordinatesFromAddress(
            $actorImportedFromUDB2->getActorId(),
            $address->toUdb3ModelAddress()
        );

        $this->commandBus->dispatch($command);
    }


    public function handleTitleUpdated(TitleUpdated $titleUpdated): void
    {
        try {
            $place = $this->placeRepository->fetch($titleUpdated->getItemId());
        } catch (DocumentDoesNotExist $e) {
            return;
        }

        $body = $place->getAssocBody();
        $language = $body['mainLanguage'] ?? array_key_first($body['address']);

        $command = new UpdateGeoCoordinatesFromAddress(
            $titleUpdated->getItemId(),
            (new AddressDenormalizer())->denormalize($body['address'][$language], Address::class)
        );

        $this->commandBus->dispatch($command);
    }
}
