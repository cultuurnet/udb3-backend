<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class LabelVisibilityOnRelatedDocumentsProjector implements EventListener, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ReadRepositoryInterface $relationRepository;
    private array $documentRepositories;

    public function __construct(
        ReadRepositoryInterface $relationRepository
    ) {
        $this->relationRepository = $relationRepository;
        $this->logger = new NullLogger();
    }

    public function withDocumentRepositoryForRelationType(
        RelationType $relationType,
        DocumentRepository $documentRepository
    ): self {
        $c = clone $this;
        $c->documentRepositories[$relationType->toString()] = $documentRepository;
        return $c;
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
        return $this->documentRepositories[$relationType->toString()] ?? null;
    }

    private function updateLabels(string $labelName, bool $madeVisible): void
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
                $document = $repository->fetch($labelRelation->getRelationId());
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

            $addToArray[] = $labelName;
            $offerLd->{$addTo} = array_values(array_unique($addToArray));

            if (isset($offerLd->{$removeFrom})) {
                $offerLd->{$removeFrom} = array_values(
                    array_diff((array) $offerLd->{$removeFrom}, [$labelName])
                );

                if (count($offerLd->{$removeFrom}) === 0) {
                    unset($offerLd->{$removeFrom});
                }
            }

            $repository->save($document->withBody($offerLd));
        }
    }
}
