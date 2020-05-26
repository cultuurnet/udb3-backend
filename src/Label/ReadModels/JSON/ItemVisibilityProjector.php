<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ItemVisibilityProjector implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var DocumentRepositoryInterface
     */
    private $itemRepository;

    /**
     * @var ReadRepositoryInterface
     */
    private $relationRepository;

    /**
     * @param DocumentRepositoryInterface $itemRepository
     * @param ReadRepositoryInterface $relationRepository
     */
    public function __construct(
        DocumentRepositoryInterface $itemRepository,
        ReadRepositoryInterface $relationRepository
    ) {
        $this->itemRepository = $itemRepository;
        $this->relationRepository = $relationRepository;
        $this->logger = new NullLogger();
    }

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $event = $domainMessage->getPayload();

        if ($event instanceof MadeVisible) {
            $this->applyMadeVisible($domainMessage->getPayload());
        } elseif ($event instanceof MadeInvisible) {
            $this->applyMadeInvisible($domainMessage->getPayload());
        }
    }

    /**
     * @param MadeVisible $madeVisible
     */
    public function applyMadeVisible(MadeVisible $madeVisible)
    {
        $this->updateLabels($madeVisible->getName(), true);
    }

    /**
     * @param MadeInvisible $madeInvisible
     */
    public function applyMadeInvisible(MadeInvisible $madeInvisible)
    {
        $this->updateLabels($madeInvisible->getName(), false);
    }

    /**
     * @param LabelName $labelName
     * @param bool $madeVisible
     */
    private function updateLabels(LabelName $labelName, $madeVisible)
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
     * @param LabelName $labelName
     * @return \CultuurNet\UDB3\ReadModel\JsonDocument[]|\Generator
     */
    private function getRelatedItems(LabelName $labelName)
    {
        $labelRelations = $this->relationRepository->getLabelRelations($labelName);

        foreach ($labelRelations as $labelRelation) {
            try {
                $document = $this->itemRepository->get((string)$labelRelation->getRelationId());

                if ($document) {
                    yield $document;
                }
            } catch (DocumentGoneException $exception) {
                $this->logger->alert(
                    'Can not update visibility of label: "'. $labelRelation->getLabelName() . '"'
                    . ' for the relation with id: "' . $labelRelation->getRelationId() . '"'
                    . ' because the document could not be retrieved.'
                );
            }
        }
    }
}
