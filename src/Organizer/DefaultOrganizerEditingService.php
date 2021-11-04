<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

class DefaultOrganizerEditingService implements OrganizerEditingServiceInterface
{
    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var UuidGeneratorInterface
     */
    protected $uuidGenerator;

    /**
     * @var Repository
     */
    protected $organizerRepository;

    public function __construct(
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        Repository $organizerRepository
    ) {
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
        $this->organizerRepository = $organizerRepository;
    }

    public function create(
        LegacyLanguage $mainLanguage,
        Url $website,
        Title $title,
        ?LegacyAddress $address = null,
        ?ContactPoint $contactPoint = null
    ): string {
        $id = $this->uuidGenerator->generate();

        $organizer = Organizer::create($id, $mainLanguage, $website, $title);

        if (!is_null($address)) {
            $organizer->updateAddress(
                new Address(
                    new Street($address->getStreetAddress()->toNative()),
                    new PostalCode($address->getPostalCode()->toNative()),
                    new Locality($address->getLocality()->toNative()),
                    new CountryCode($address->getCountry()->getCode()->toNative())
                ),
                new Language($mainLanguage->getCode())
            );
        }

        if (!is_null($contactPoint)) {
            $organizer->updateContactPoint($contactPoint);
        }

        $this->organizerRepository->save($organizer);

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function delete(string $id): void
    {
        $this->commandBus->dispatch(
            new DeleteOrganizer($id)
        );
    }
}
