<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class LabelVisibilityOnRelatedDocumentsProjector implements EventListener, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private DocumentRepository $documentRepository;
    private ReadRepositoryInterface $relationRepository;

    public function __construct(
        DocumentRepository $documentRepository,
        ReadRepositoryInterface $relationRepository
    ) {
        $this->documentRepository = $documentRepository;
        $this->relationRepository = $relationRepository;
        $this->logger = new NullLogger();
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        if ($event instanceof MadeVisible) {
            $this->applyMadeVisible($domainMessage->getPayload());
        } elseif ($event instanceof MadeInvisible) {
            $this->applyMadeInvisible($domainMessage->getPayload());
        }
    }

    public function applyMadeVisible(MadeVisible $madeVisible): void
    {
        $this->updateLabels($madeVisible->getName(), true);
    }

    public function applyMadeInvisible(MadeInvisible $madeInvisible): void
    {
        $this->updateLabels($madeInvisible->getName(), false);
    }

    private function getDocumentRepositoryForRelationType(RelationType $relationType): ?DocumentRepository
    {
        return $this->documentRepository;
    }

    private function updateLabels(LegacyLabelName $labelName, bool $madeVisible): void
    {
        $labelRelations = $this->relationRepository->getLabelRelations($labelName);

        $removeFrom = $madeVisible ? 'hiddenLabels' : 'labels';
        $addTo = $madeVisible ? 'labels' : 'hiddenLabels';

        foreach ($labelRelations as $labelRelation) {
            $relationType = $labelRelation->getRelationType();
            $repository = $this->getDocumentRepositoryForRelationType($relationType);

            if (!$repository) {
                $this->logger->error(
                    sprintf(
                        'Can not update visibility of label: "%s" for the relation with id "%s" because '
                        . 'no document repository configured for relation type "%s".',
                        $labelRelation->getLabelName(),
                        $labelRelation->getRelationId(),
                        $relationType->toString()
                    )
                );
            }

            try {
                $document = $repository->fetch((string) $labelRelation->getRelationId());
            } catch (DocumentDoesNotExist $exception) {
                $this->logger->error(
                    sprintf(
                        'Can not update visibility of label: "%s" for the relation with id "%s" because '
                        . 'the document could not be retrieved.',
                        $labelRelation->getLabelName(),
                        $labelRelation->getRelationId()
                    )
                );
                continue;
            }

            $offerLd = $document->getBody();

            $addToArray = isset($offerLd->{$addTo}) ? (array) $offerLd->{$addTo} : [];

            $addToArray[] = $labelName->toNative();
            $offerLd->{$addTo} = array_values(array_unique($addToArray));

            if (isset($offerLd->{$removeFrom})) {
                $offerLd->{$removeFrom} = array_values(
                    array_diff((array) $offerLd->{$removeFrom}, [$labelName])
                );

                if (count($offerLd->{$removeFrom}) === 0) {
                    unset($offerLd->{$removeFrom});
                }
            }

            $this->documentRepository->save($document->withBody($offerLd));
        }
    }
}
