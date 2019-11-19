<?php

namespace CultuurNet\UDB3\Organizer;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Repository\RepositoryInterface;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Organizer\Commands\AddLabel;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Organizer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Organizer\Commands\RemoveLabel;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

class DefaultOrganizerEditingService implements OrganizerEditingServiceInterface
{
    /**
     * @var CommandBusInterface
     */
    protected $commandBus;

    /**
     * @var UuidGeneratorInterface
     */
    protected $uuidGenerator;

    /**
     * @var RepositoryInterface
     */
    protected $organizerRepository;

    /**
     * @var LabelServiceInterface
     */
    protected $labelService;

    public function __construct(
        CommandBusInterface $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        RepositoryInterface $organizerRepository,
        LabelServiceInterface $labelService
    ) {
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
        $this->organizerRepository = $organizerRepository;
        $this->labelService = $labelService;
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
    public function updateTitle(string $organizerId, Title $title, Language $language): void
    {
        $this->commandBus->dispatch(
            new UpdateTitle($organizerId, $title, $language)
        );
    }

    /**
     * @inheritdoc
     */
    public function updateAddress(string $organizerId, Address $address, Language $language): void
    {
        $this->commandBus->dispatch(
            new UpdateAddress($organizerId, $address, $language)
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
    public function addLabel(string $organizerId, Label $label): void
    {
        $this->labelService->createLabelAggregateIfNew(
            new LabelName((string) $label),
            $label->isVisible()
        );

        $this->commandBus->dispatch(
            new AddLabel($organizerId, $label)
        );
    }

    /**
     * @inheritdoc
     */
    public function removeLabel(string $organizerId, Label $label): void
    {
        $this->commandBus->dispatch(
            new RemoveLabel($organizerId, $label)
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
}
