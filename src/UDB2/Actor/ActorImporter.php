<?php

namespace CultuurNet\UDB3\UDB2\Actor;

use Broadway\EventHandling\EventListenerInterface;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\CdbXmlContainerInterface;
use CultuurNet\UDB3\Cdb\UpdateableWithCdbXmlInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Media\Properties\UnsupportedMIMETypeException;
use CultuurNet\UDB3\Organizer\Organizer;
use CultuurNet\UDB3\Place\Place;
use CultuurNet\UDB3\UDB2\Actor\Events\ActorCreatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\Actor\Events\ActorUpdatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\Actor\Specification\ActorSpecificationInterface;
use CultuurNet\UDB3\UDB2\Label\LabelApplierInterface;
use CultuurNet\UDB3\UDB2\Media\MediaImporter;
use CultuurNet\UDB3\UDB2\OfferAlreadyImportedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Applies incoming UDB2 actor events enriched with cdb xml on UDB3 organizers.
 *
 * Whether the UDB2 actor event should be processed is defined by an
 * implementation of ActorSpecificationInterface.
 *
 * Instantiation of new entities is delegated to an implementation of
 * ActorToUDB3AggregateFactoryInterface.
 *
 * Entities targeted by the ActorEventApplier need to implement
 * UpdateableWithCdbXmlInterface.
 */
class ActorImporter implements EventListenerInterface, LoggerAwareInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;
    use LoggerAwareTrait;

    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var ActorSpecificationInterface
     */
    protected $actorSpecification;

    /**
     * @var ActorToUDB3AggregateFactoryInterface
     */
    protected $actorFactory;

    /**
     * @var MediaImporter|null
     */
    protected $mediaImporter;

    /**
     * @var LabelApplierInterface
     */
    private $labelApplier;

    public function __construct(
        RepositoryInterface $repository,
        ActorToUDB3AggregateFactoryInterface $actorFactory,
        ActorSpecificationInterface $actorSpecification,
        LabelApplierInterface $labelApplier,
        MediaImporter $mediaImporter = null
    ) {
        $this->repository = $repository;
        $this->actorSpecification = $actorSpecification;
        $this->actorFactory = $actorFactory;
        $this->labelApplier = $labelApplier;
        $this->mediaImporter = $mediaImporter;

        $this->logger = new NullLogger();
    }

    private function applyActorCreatedEnrichedWithCdbXml(
        ActorCreatedEnrichedWithCdbXml $actorCreated
    ): void {
        if (!$this->isSatisfiedBy($actorCreated)) {
            return;
        }

        $this->createWithUpdateFallback(
            $actorCreated->getActorId(),
            $actorCreated
        );
    }

    private function applyActorUpdatedEnrichedWithCdbXml(
        ActorUpdatedEnrichedWithCdbXml $actorUpdated
    ): void {
        if (!$this->isSatisfiedBy($actorUpdated)) {
            return;
        }

        $this->updateWithCreateFallback(
            $actorUpdated->getActorId(),
            $actorUpdated
        );
    }

    private function isSatisfiedBy(CdbXmlContainerInterface $actorCdbXml): bool
    {
        $actor = ActorItemFactory::createActorFromCdbXml(
            $actorCdbXml->getCdbXmlNamespaceUri(),
            $actorCdbXml->getCdbXml()
        );

        $satisfied = $this->actorSpecification->isSatisfiedBy($actor);

        if (!$satisfied && $this->logger) {
            $this->logger->debug(
                'The specification of which actors need to be processed is ' .
                'not satisfied by UDB2 actor with cdbid: ' . $actor->getCdbId()
            );
        }

        return $satisfied;
    }

    private function updateWithCreateFallback(
        StringLiteral $entityId,
        CdbXmlContainerInterface $cdbXml
    ): void {
        try {
            $this->update($entityId, $cdbXml);

            $this->logger->debug(
                'Actor succesfully updated.'
            );
        } catch (AggregateNotFoundException $e) {
            $this->logger->debug(
                'Update failed because entity did not exist yet, trying to create it as a fallback.'
            );

            $this->create($entityId, $cdbXml);

            $this->logger->debug(
                'Actor succesfully created.'
            );
        }
    }

    private function createWithUpdateFallback(
        StringLiteral $entityId,
        CdbXmlContainerInterface $cdbXml
    ) {
        try {
            $this->create($entityId, $cdbXml);

            $this->logger->debug(
                'Actor succesfully created.'
            );
        } catch (OfferAlreadyImportedException $e) {
            $this->logger->debug(
                'An offer with the same id already exists, trying to update as a fallback.'
            );

            $this->update($entityId, $cdbXml);

            $this->logger->debug(
                'Actor succesfully updated.'
            );
        }
    }

    private function update(
        StringLiteral $entityId,
        CdbXmlContainerInterface $cdbXml
    ): void {
        $entityId = (string) $entityId;

        /** @var Organizer|Place $entity */
        $entity = $this->repository->load($entityId);

        $entity->updateWithCdbXml(
            $cdbXml->getCdbXml(),
            $cdbXml->getCdbXmlNamespaceUri()
        );

        if ($this->mediaImporter) {
            $cdbActor = ActorItemFactory::createActorFromCdbXml(
                $cdbXml->getCdbXmlNamespaceUri(),
                $cdbXml->getCdbXml()
            );

            $imageCollection = $this->mediaImporter->importImages($cdbActor);
            $entity->updateImagesFromUDB2($imageCollection);
        }

        $this->labelApplier->apply($entity);

        $this->repository->save($entity);
    }

    private function create(
        StringLiteral $id,
        CdbXmlContainerInterface $cdbXml
    ): void {
        try {
            $this->repository->load((string) $id);
            throw new OfferAlreadyImportedException('An offer with id: ' . $id . 'was already imported.');
        } catch (AggregateNotFoundException $e) {
            $this->logger->info(
                'No existing offer with the same id found so it is safe to import.',
                [
                    'offer-id' => (string) $id,
                ]
            );
        }

        /** @var Place|Organizer $entity */
        $entity = $this->actorFactory->createFromCdbXml(
            (string) $id,
            $cdbXml->getCdbXml(),
            $cdbXml->getCdbXmlNamespaceUri()
        );

        if ($this->mediaImporter) {
            $cdbActor = ActorItemFactory::createActorFromCdbXml(
                $cdbXml->getCdbXmlNamespaceUri(),
                $cdbXml->getCdbXml()
            );

            try {
                $imageCollection = $this->mediaImporter->importImages($cdbActor);
                if ($imageCollection->length() > 0) {
                    $entity->importImagesFromUDB2($imageCollection);
                }
            } catch (UnsupportedMIMETypeException $e) {
                $this->logger->error(
                    'Unable to import images for offer. ' . $e->getMessage(),
                    ['offer-id' => (string) $id]
                );
            };
        }

        $this->repository->save($entity);
    }
}
