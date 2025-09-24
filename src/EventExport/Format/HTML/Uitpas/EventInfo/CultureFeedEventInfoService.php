<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo;

use CultureFeed_PointsPromotion;
use CultureFeed_Uitpas;
use CultureFeed_Uitpas_CardSystem;
use CultureFeed_Uitpas_DistributionKey;
use CultureFeed_Uitpas_Event_CultureEvent;
use CultureFeed_Uitpas_Event_Query_SearchEventsOptions;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey\DistributionKeySpecification;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey\KansentariefForCurrentCardSystemSpecification;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey\KansentariefForOtherCardSystemsSpecification;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event\EventAdvantage;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event\PointCollectingSpecification;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Promotion\PromotionQueryFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class CultureFeedEventInfoService implements EventInfoServiceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private CultureFeed_Uitpas $uitpas;

    private DistributionKeySpecification $kansenTariefForCurrentCardSystem;

    private DistributionKeySpecification $kansenTariefForOtherCardSystems;

    private PointCollectingSpecification $pointCollecting;

    private PromotionQueryFactoryInterface $promotionQueryFactory;

    public function __construct(
        CultureFeed_Uitpas $uitpas,
        PromotionQueryFactoryInterface $promotionQueryFactory
    ) {
        $this->uitpas = $uitpas;

        $this->kansenTariefForCurrentCardSystem =
            new KansentariefForCurrentCardSystemSpecification();

        $this->kansenTariefForOtherCardSystems =
            new KansentariefForOtherCardSystemsSpecification();

        $this->pointCollecting = new PointCollectingSpecification();

        $this->promotionQueryFactory = $promotionQueryFactory;
    }

    public function getEventInfo(string $eventId): EventInfo
    {
        $prices = [];
        $advantages = [];
        $promotions = [];

        $eventQuery =
            new CultureFeed_Uitpas_Event_Query_SearchEventsOptions();

        $eventQuery->cdbid = $eventId;

        $resultSet = $this->uitpas->searchEvents($eventQuery);

        /** @var CultureFeed_Uitpas_Event_CultureEvent|false $uitpasEvent */
        $uitpasEvent = reset($resultSet->objects);

        try {
            if ($uitpasEvent) {
                $advantages = $this->getUitpasAdvantagesFromEvent($uitpasEvent);

                $prices = $this->getUitpasPricesFromEvent($uitpasEvent);

                $promotions = $this->getUitpasPointsPromotionsFromEvent($uitpasEvent);
            }
            $advantages = array_unique($advantages);
        } catch (\Exception $exception) {
            $prices = [];
            $advantages = [];
        }

        return new EventInfo(
            $prices,
            $advantages,
            $promotions
        );
    }

    private function getUitpasPricesFromEvent(CultureFeed_Uitpas_Event_CultureEvent $event): array
    {
        $prices = [];

        foreach ($event->cardSystems as $cardSystem) {
            foreach ($cardSystem->distributionKeys as $key) {
                $prices = array_merge(
                    $prices,
                    $this->getUitpasPricesFromDistributionKey(
                        $cardSystem,
                        $key
                    )
                );
            }
        }

        return $prices;
    }

    /**
     * @return EventAdvantage[]
     */
    private function getUitpasAdvantagesFromEvent(CultureFeed_Uitpas_Event_CultureEvent $event): array
    {
        $advantages = [];

        if ($this->pointCollecting->isSatisfiedBy($event)) {
            $advantages[] = EventAdvantage::pointCollecting();
        }

        return $advantages;
    }

    /**
     * @return string[]
     */
    private function getUitpasPointsPromotionsFromEvent(\CultureFeed_Uitpas_Event_CultureEvent $event): array
    {
        $promotions = [];
        $promotionQuery = $this->promotionQueryFactory->createForEvent($event);

        /** @var CultureFeed_PointsPromotion[] $promotionQueryResults */
        $promotionQueryResults = [];


        /**
         * @see https://jira.publiq.be/browse/III-6439
         */
        try {
            $promotionQueryResults = $this->uitpas->getPromotionPoints($promotionQuery)->objects;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->info(
                    'Can\'t retrieve promotions for event with id:' . $event->cdbid . ' eventOrganizer may no longer be UiTPAS',
                    ['exception' => $e]
                );
            }
        };

        foreach ($promotionQueryResults as $promotionsQueryResult) {
            if ($promotionsQueryResult->points === 1.0) {
                $pointChoice = 'pt';
            } else {
                $pointChoice = 'ptn';
            }

            $promotion = sprintf(
                '%s (%s %s)',
                $promotionsQueryResult->title,
                $promotionsQueryResult->points,
                $pointChoice
            );
            $promotions[] = $promotion;
        }

        return $promotions;
    }

    private function getUitpasPricesFromDistributionKey(
        CultureFeed_Uitpas_CardSystem $cardSystem,
        CultureFeed_Uitpas_DistributionKey $key
    ): array {
        $uitpasPrices = [];

        $tariffAsNumeric = (float) $key->tariff;

        if ($this->kansenTariefForCurrentCardSystem->isSatisfiedBy($key)) {
            $uitpasPrices[] = [
                'price' => $tariffAsNumeric,
                'cardSystem' => $cardSystem->getName(),
                'forOtherCardSystems' => false,
            ];
        }

        if ($this->kansenTariefForOtherCardSystems->isSatisfiedBy($key)) {
            $uitpasPrices[] = [
                'price' => $tariffAsNumeric,
                'cardSystem' => $cardSystem->getName(),
                'forOtherCardSystems' => true,
            ];
        }

        return $uitpasPrices;
    }
}
