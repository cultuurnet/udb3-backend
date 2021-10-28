<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\Commands\RemoveAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
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
        Language $mainLanguage,
        Url $website,
        Title $title,
        ?Address $address = null,
        ?ContactPoint $contactPoint = null
    ): string {
        $id = $this->uuidGenerator->generate();

        $organizer = Organizer::create($id, $mainLanguage, $website, $title);

        if (!is_null($address)) {
            $organizer->updateAddress($address, $mainLanguage);
        }

        if (!is_null($contactPoint)) {
            $organizer->updateContactPoint($contactPoint);
        }

        $this->organizerRepository->save($organizer);

        return $id;
    }

    public function updateWebsite(string $organizerId, Url $website): void
    {
        $this->commandBus->dispatch(
            new UpdateWebsite($organizerId, $website)
        );
    }

    /**
     * @inheritdoc
     */
    public function updateContactPoint(string $organizerId, ContactPoint $contactPoint): void
    {
        $this->commandBus->dispatch(
            new UpdateContactPoint($organizerId, $contactPoint)
        );
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

    public function removeAddress(string $organizerId): void
    {
        $this->commandBus->dispatch(
            new RemoveAddress($organizerId)
        );
    }
}
