<?php

namespace CultuurNet\UDB3\Organizer;

use Broadway\CommandHandling\CommandHandlerInterface;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Organizer\Commands\AbstractLabelCommand;
use CultuurNet\UDB3\Organizer\Commands\AddLabel;
use CultuurNet\UDB3\Organizer\Commands\CreateOrganizer;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\Commands\RemoveLabel;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use ValueObjects\StringLiteral\StringLiteral;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;

class OrganizerCommandHandler implements CommandHandlerInterface
{
    /**
     * @var RepositoryInterface
     */
    private $organizerRepository;

    /**
     * @var ReadRepositoryInterface
     */
    private $labelRepository;

    /**
     * @var OrganizerRelationServiceInterface[]
     */
    private $organizerRelationServices;

    /**
     * @param RepositoryInterface $organizerRepository
     * @param ReadRepositoryInterface $labelRepository
     */
    public function __construct(
        RepositoryInterface $organizerRepository,
        ReadRepositoryInterface $labelRepository
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->labelRepository = $labelRepository;
        $this->organizerRelationServices = [];
    }

    /**
     * @param OrganizerRelationServiceInterface $relationService
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
            UpdateContactPoint::class => 'updateContactPoint',
            DeleteOrganizer::class => 'deleteOrganizer',
            AddLabel::class => 'addLabel',
            RemoveLabel::class => 'removeLabel',
            ImportLabels::class => 'importLabels',
        ];
    }

    /**
     * @param mixed $command
     */
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

    /**
     * @param UpdateWebsite $updateWebsite
     */
    protected function updateWebsite(UpdateWebsite $updateWebsite)
    {
        $organizer = $this->loadOrganizer($updateWebsite->getOrganizerId());

        $organizer->updateWebsite($updateWebsite->getWebsite());

        $this->organizerRepository->save($organizer);
    }

    /**
     * @param UpdateTitle $updateTitle
     */
    protected function updateTitle(UpdateTitle $updateTitle)
    {
        $organizer = $this->loadOrganizer($updateTitle->getOrganizerId());

        $organizer->updateTitle(
            $updateTitle->getTitle(),
            $updateTitle->getLanguage()
        );

        $this->organizerRepository->save($organizer);
    }

    /**
     * @param UpdateAddress $updateAddress
     */
    protected function updateAddress(UpdateAddress $updateAddress)
    {
        $organizer = $this->loadOrganizer($updateAddress->getOrganizerId());

        $organizer->updateAddress(
            $updateAddress->getAddress(),
            $updateAddress->getLanguage()
        );

        $this->organizerRepository->save($organizer);
    }

    /**
     * @param UpdateContactPoint $updateContactPoint
     */
    protected function updateContactPoint(UpdateContactPoint $updateContactPoint)
    {
        $organizer = $this->loadOrganizer($updateContactPoint->getOrganizerId());

        $organizer->updateContactPoint($updateContactPoint->getContactPoint());

        $this->organizerRepository->save($organizer);
    }

    /**
     * @param AddLabel $addLabel
     */
    protected function addLabel(AddLabel $addLabel)
    {
        $organizer = $this->loadOrganizer($addLabel->getOrganizerId());

        $organizer->addLabel($this->createLabel($addLabel));

        $this->organizerRepository->save($organizer);
    }

    /**
     * @param RemoveLabel $removeLabel
     */
    protected function removeLabel(RemoveLabel $removeLabel)
    {
        $organizer = $this->loadOrganizer($removeLabel->getOrganizerId());

        $organizer->removeLabel($this->createLabel($removeLabel));

        $this->organizerRepository->save($organizer);
    }

    /**
     * @param ImportLabels $importLabels
     */
    protected function importLabels(ImportLabels $importLabels)
    {
        $organizer = $this->loadOrganizer($importLabels->getOrganizerId());

        $organizer->importLabels($importLabels->getLabels(), $importLabels->getLabelsToKeepIfAlreadyOnOrganizer());

        $this->organizerRepository->save($organizer);
    }

    /**
     * @param AbstractLabelCommand $labelCommand
     * @return Label
     */
    private function createLabel(AbstractLabelCommand $labelCommand)
    {
        $labelName = new StringLiteral((string) $labelCommand->getLabel());
        $label = $this->labelRepository->getByName($labelName);

        return new Label(
            $labelName->toNative(),
            $label->getVisibility() === Visibility::VISIBLE()
        );
    }

    /**
     * @param DeleteOrganizer $deleteOrganizer
     */
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

    /**
     * Makes it easier to type hint to Organizer.
     *
     * @param string $id
     * @return Organizer
     */
    protected function loadOrganizer($id)
    {
        return $this->organizerRepository->load($id);
    }
}
