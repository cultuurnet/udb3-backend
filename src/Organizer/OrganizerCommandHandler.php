<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\CommandHandling\CommandHandler;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;

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

    public function deleteOrganizer(DeleteOrganizer $deleteOrganizer)
    {
        $id = $deleteOrganizer->getItemId();

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
