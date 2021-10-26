<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\CommandHandling\CommandHandler;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Organizer\Commands\CreateOrganizer;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\Commands\RemoveAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;

class OrganizerCommandHandler implements CommandHandler
{
    /**
     * @var Repository
     */
    private $organizerRepository;

    /**
     * @var OrganizerRelationServiceInterface[]
     */
    private $organizerRelationServices;

    public function __construct(Repository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
        $this->organizerRelationServices = [];
    }

    /**
     * @return OrganizerCommandHandler
     */
    public function withOrganizerRelationService(OrganizerRelationServiceInterface $relationService)
    {
        $c = clone $this;
        $c->organizerRelationServices[] = $relationService;
        return $c;
    }

    /**
     * @return array
     */
    protected function getCommandHandlerMethods()
    {
        return [
            CreateOrganizer::class => 'createOrganizer',
            UpdateWebsite::class => 'updateWebsite',
            UpdateTitle::class => 'updateTitle',
            UpdateAddress::class => 'updateAddress',
            RemoveAddress::class => 'removeAddress',
            UpdateContactPoint::class => 'updateContactPoint',
            DeleteOrganizer::class => 'deleteOrganizer',
        ];
    }


    public function handle($command)
    {
        $class = get_class($command);
        $handlers = $this->getCommandHandlerMethods();

        if (isset($handlers[$class])) {
            $method = $handlers[$class];
            $this->{$method}($command);
        }
    }

    protected function createOrganizer(CreateOrganizer $createOrganizer)
    {
        $organizer = Organizer::create(
            $createOrganizer->getOrganizerId(),
            $createOrganizer->getMainLanguage(),
            $createOrganizer->getWebsite(),
            $createOrganizer->getTitle()
        );

        $this->organizerRepository->save($organizer);
    }


    protected function updateWebsite(UpdateWebsite $updateWebsite)
    {
        $organizer = $this->loadOrganizer($updateWebsite->getOrganizerId());

        $organizer->updateWebsite($updateWebsite->getWebsite());

        $this->organizerRepository->save($organizer);
    }


    protected function updateAddress(UpdateAddress $updateAddress)
    {
        $organizer = $this->loadOrganizer($updateAddress->getOrganizerId());

        $organizer->updateAddress(
            $updateAddress->getAddress(),
            $updateAddress->getLanguage()
        );

        $this->organizerRepository->save($organizer);
    }

    public function removeAddress(RemoveAddress $removeAddress)
    {
        $organizer = $this->loadOrganizer($removeAddress->getOrganizerId());

        $organizer->removeAddress();

        $this->organizerRepository->save($organizer);
    }


    protected function updateContactPoint(UpdateContactPoint $updateContactPoint)
    {
        $organizer = $this->loadOrganizer($updateContactPoint->getOrganizerId());

        $organizer->updateContactPoint($updateContactPoint->getContactPoint());

        $this->organizerRepository->save($organizer);
    }

    public function deleteOrganizer(DeleteOrganizer $deleteOrganizer)
    {
        $id = $deleteOrganizer->getOrganizerId();

        // First remove all relations to the given organizer.
        foreach ($this->organizerRelationServices as $relationService) {
            $relationService->deleteOrganizer($id);
        }

        // Delete the organizer itself.
        $organizer = $this->loadOrganizer($id);

        $organizer->delete();

        $this->organizerRepository->save($organizer);
    }

    protected function loadOrganizer(string $id): Organizer
    {
        /** @var Organizer $organizer */
        $organizer = $this->organizerRepository->load($id);

        return $organizer;
    }
}
