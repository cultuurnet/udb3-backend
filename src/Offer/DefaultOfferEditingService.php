<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Offer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class DefaultOfferEditingService implements OfferEditingServiceInterface
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
     * @var DocumentRepository
     */
    protected $readRepository;

    /**
     * @var OfferCommandFactoryInterface
     */
    protected $commandFactory;

    /**
     * @var \DateTimeImmutable|null
     */
    protected $publicationDate;

    public function __construct(
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator,
        DocumentRepository $readRepository,
        OfferCommandFactoryInterface $commandFactory
    ) {
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
        $this->readRepository = $readRepository;
        $this->commandFactory = $commandFactory;
        $this->publicationDate = null;
    }

    /**
     * @return static
     */
    public function withFixedPublicationDateForNewOffers(
        \DateTimeImmutable $publicationDate
    ) {
        $c = clone $this;
        $c->publicationDate = $publicationDate;
        return $c;
    }

    public function deleteOrganizer(string $id, string $organizerId): void
    {
        $this->guardId($id);
        $this->commandBus->dispatch(new DeleteOrganizer($id, $organizerId));
    }

    public function guardId(string $id): void
    {
        try {
            $this->readRepository->fetch($id);
        } catch (DocumentDoesNotExist $e) {
            throw new EntityNotFoundException(
                sprintf('Offer with id: %s not found.', $id)
            );
        }
    }
}
