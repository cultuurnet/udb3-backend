<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Generator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ItemVisibilityProjector implements EventListener, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private DocumentRepository $itemRepository;
    private ReadRepositoryInterface $relationRepository;

    public function __construct(
        DocumentRepository $itemRepository,
        ReadRepositoryInterface $relationRepository
    ) {
        $this->itemRepository = $itemRepository;
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

    private function updateLabels(LabelName $labelName, bool $madeVisible): void
    {
        $items = $this->getRelatedItems($labelName);

        $removeFrom = $madeVisible ? 'hiddenLabels' : 'labels';
        $addTo = $madeVisible ? 'labels' : 'hiddenLabels';

        foreach ($items as $item) {
            $offerLd = $item->getBody();

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

            $this->itemRepository->save($item->withBody($offerLd));
        }
    }

    /**
     * @return JsonDocument[]|Generator
     */
    private function getRelatedItems(LabelName $labelName): Generator
    {
        $labelRelations = $this->relationRepository->getLabelRelations($labelName);

        foreach ($labelRelations as $labelRelation) {
            try {
                yield $this->itemRepository->fetch((string)$labelRelation->getRelationId());
            } catch (DocumentDoesNotExist $exception) {
                $this->logger->alert(
                    'Can not update visibility of label: "' . $labelRelation->getLabelName() . '"'
                    . ' for the relation with id: "' . $labelRelation->getRelationId() . '"'
                    . ' because the document could not be retrieved.'
                );
            }
        }
    }
}
