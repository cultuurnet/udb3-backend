<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ProcessManager;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultureFeed_Cdb_Data_Address;
use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactoryInterface;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Organizer\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Organizer\Events\AddressTranslated;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class GeoCoordinatesProcessManager implements EventListener
{
    private CommandBus $commandBus;

    private CultureFeedAddressFactoryInterface $addressFactory;

    private LoggerInterface $logger;

    public function __construct(
        CommandBus $commandBus,
        CultureFeedAddressFactoryInterface $addressFactory,
        LoggerInterface $logger
    ) {
        $this->commandBus = $commandBus;
        $this->addressFactory = $addressFactory;
        $this->logger = $logger;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        if ($event instanceof AddressUpdated && !$event instanceof AddressTranslated) {
            $this->handleAddressUpdated($event);
        }

        if ($event instanceof OrganizerImportedFromUDB2 || $event instanceof OrganizerUpdatedFromUDB2) {
            $this->handleOrganizerFromUDB2($event);
        }
    }

    public function handleAddressUpdated(AddressUpdated $event): void
    {
        $this->commandBus->dispatch(
            new UpdateGeoCoordinatesFromAddress(
                $event->getOrganizerId(),
                new Address(
                    new Street($event->getStreetAddress()),
                    new PostalCode($event->getPostalCode()),
                    new Locality($event->getLocality()),
                    new CountryCode($event->getCountryCode())
                )
            )
        );
    }

    public function handleOrganizerFromUDB2(ActorImportedFromUDB2 $actorImportedFromUDB2): void
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
        } catch (InvalidArgumentException $e) {
            // If conversion failed, log an error and do nothing.
            $this->logger->error(
                'Could not convert a cdbxml address to a udb3 address for geocoding.',
                [
                    'organizerId' => $actorImportedFromUDB2->getActorId(),
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
            $address
        );

        $this->commandBus->dispatch($command);
    }
}
