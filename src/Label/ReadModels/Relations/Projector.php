<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Label\LabelEventRelationTypeResolverInterface;
use CultuurNet\UDB3\Label\ReadModels\AbstractProjector;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\LabelRelation;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\LabelsImportedEventInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\LabelsArray;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class Projector extends AbstractProjector
{
    private WriteRepositoryInterface $writeRepository;

    private ReadRepositoryInterface $readRepository;

    private LabelEventRelationTypeResolverInterface $offerTypeResolver;

    public function __construct(
        WriteRepositoryInterface $writeRepository,
        ReadRepositoryInterface $readRepository,
        LabelEventRelationTypeResolverInterface $labelEventOfferTypeResolver
    ) {
        $this->writeRepository = $writeRepository;
        $this->readRepository = $readRepository;
        $this->offerTypeResolver = $labelEventOfferTypeResolver;
    }

    public function applyLabelAdded(LabelEventInterface $labelAdded, Metadata $metadata): void
    {
        $LabelRelation = $this->createLabelRelation($labelAdded);

        try {
            $this->writeRepository->save(
                $LabelRelation->getLabelName(),
                $LabelRelation->getRelationType(),
                $LabelRelation->getRelationId(),
                false
            );
        } catch (UniqueConstraintViolationException $exception) {
            // By design to catch unique exception.
        }
    }

    public function applyLabelRemoved(LabelEventInterface $labelRemoved, Metadata $metadata): void
    {
        $this->writeRepository->deleteByLabelNameAndRelationId(
            $labelRemoved->getLabelName(),
            $labelRemoved->getItemId()
        );
    }

    public function applyLabelsImported(LabelsImportedEventInterface $labelsImported, Metadata $metadata): void
    {
        foreach ($labelsImported->getAllLabelNames() as $labelName) {
            try {
                $this->writeRepository->save(
                    $labelName,
                    $this->offerTypeResolver->getRelationTypeForImport($labelsImported),
                    $labelsImported->getItemId(),
                    true
                );
            } catch (UniqueConstraintViolationException $exception) {
                // By design to catch unique exception.
            }
        }
    }

    public function applyLabelsReplaced(LabelsImportedEventInterface $labelsReplaced, Metadata $metadata): void
    {
        foreach ($labelsReplaced->getAllLabelNames() as $labelName) {
            try {
                $this->writeRepository->save(
                    $labelName,
                    $this->offerTypeResolver->getRelationTypeForReplaceLabel($labelsReplaced),
                    $labelsReplaced->getItemId(),
                    true
                );
            } catch (UniqueConstraintViolationException $exception) {
                // By design to catch unique exception.
            }
        }
    }

    public function applyEventImportedFromUDB2(
        EventImportedFromUDB2 $eventImportedFromUDB2
    ): void {
        $event = EventItemFactory::createEventFromCdbXml(
            $eventImportedFromUDB2->getCdbXmlNamespaceUri(),
            $eventImportedFromUDB2->getCdbXml()
        );

        $this->updateLabelRelationFromCdbItem($event, RelationType::event());
    }

    public function applyPlaceImportedFromUDB2(
        PlaceImportedFromUDB2 $placeImportedFromUDB2
    ): void {
        $place = ActorItemFactory::createActorFromCdbXml(
            $placeImportedFromUDB2->getCdbXmlNamespaceUri(),
            $placeImportedFromUDB2->getCdbXml()
        );

        $this->updateLabelRelationFromCdbItem($place, RelationType::place());
    }

    public function applyOrganizerImportedFromUDB2(
        OrganizerImportedFromUDB2 $organizerImportedFromUDB2
    ): void {
        $organizer = ActorItemFactory::createActorFromCdbXml(
            $organizerImportedFromUDB2->getCdbXmlNamespaceUri(),
            $organizerImportedFromUDB2->getCdbXml()
        );

        $this->updateLabelRelationFromCdbItem($organizer, RelationType::organizer());
    }

    public function applyEventUpdatedFromUDB2(
        EventUpdatedFromUDB2 $eventUpdatedFromUDB2
    ): void {
        $event = EventItemFactory::createEventFromCdbXml(
            $eventUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $eventUpdatedFromUDB2->getCdbXml()
        );

        $this->updateLabelRelationFromCdbItem($event, RelationType::event());
    }

    public function applyPlaceUpdatedFromUDB2(
        PlaceUpdatedFromUDB2 $placeUpdatedFromUDB2
    ): void {
        $place = ActorItemFactory::createActorFromCdbXml(
            $placeUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $placeUpdatedFromUDB2->getCdbXml()
        );

        $this->updateLabelRelationFromCdbItem($place, RelationType::place());
    }

    public function applyOrganizerUpdatedFromUDB2(
        OrganizerUpdatedFromUDB2 $organizerUpdatedFromUDB2
    ): void {
        $organizer = ActorItemFactory::createActorFromCdbXml(
            $organizerUpdatedFromUDB2->getCdbXmlNamespaceUri(),
            $organizerUpdatedFromUDB2->getCdbXml()
        );

        $this->updateLabelRelationFromCdbItem($organizer, RelationType::organizer());
    }

    private function updateLabelRelationFromCdbItem(
        \CultureFeed_Cdb_Item_Base $cdbItem,
        RelationType $relationType
    ): void {
        $relationId = $cdbItem->getCdbId();

        // Never delete the UDB3 labels on an update.
        $this->writeRepository->deleteImportedByRelationId($relationId);

        $labelsArray = new LabelsArray();
        foreach ($cdbItem->getKeywords() as $keyword) {
            $labelsArray->addLabel($keyword, true);
        }

        // Calculate the UDB2 imported labels.
        $udb3Labels = array_map(
            static fn (LabelRelation $labelRelation) => $labelRelation->getLabelName(),
            $this->readRepository->getLabelRelationsForItem($relationId)
        );
        $udb2Labels = array_udiff(
            $labelsArray->toArrayOfStringNames(),
            $udb3Labels,
            'strcasecmp'
        );

        // Only save the UDB2 labels, because the UDB3 labels are still present.
        foreach ($udb2Labels as $label) {
            $this->writeRepository->save(
                $label,
                $relationType,
                $relationId,
                true
            );
        }
    }

    private function createLabelRelation(LabelEventInterface $labelEvent): LabelRelation
    {
        return new LabelRelation(
            $labelEvent->getLabelName(),
            $this->offerTypeResolver->getRelationType($labelEvent),
            $labelEvent->getItemId(),
            false
        );
    }
}
