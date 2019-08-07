<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo;

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

    /**
     * @var CultureFeed_Uitpas
     */
    protected $uitpas;

    /**
     * @var DistributionKeySpecification
     */
    protected $kansenTariefForCurrentCardSystem;

    /**
     * @var DistributionKeySpecification
     */
    protected $kansenTariefForOtherCardSystems;

    /**
     * @var PointCollectingSpecification
     */
    protected $pointCollecting;

    /**
     * @var PromotionQueryFactoryInterface
     */
    protected $promotionQueryFactory;

    /**
     * @param CultureFeed_Uitpas             $uitpas
     * @param PromotionQueryFactoryInterface $promotionQueryFactory
     */
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

    /**
     * @inheritdoc
     */
    public function getEventInfo($eventId)
    {
        $prices = [];
        $advantages = [];
        $promotions = [];

        $eventQuery =
            new CultureFeed_Uitpas_Event_Query_SearchEventsOptions();

        $eventQuery->cdbid = $eventId;

        $resultSet = $this->uitpas->searchEvents($eventQuery);

        /**
 * @var CultureFeed_Uitpas_Event_CultureEvent $uitpasEvent
*/
        $uitpasEvent = reset($resultSet->objects);

        if ($uitpasEvent) {
            $advantages = $this->getUitpasAdvantagesFromEvent($uitpasEvent);

            $prices = $this->getUitpasPricesFromEvent($uitpasEvent);

            $promotions = $this->getUitpasPointsPromotionsFromEvent($uitpasEvent);
        }
        $advantages = array_unique($advantages);

        return new EventInfo(
            $prices,
            $advantages,
            $promotions
        );
    }

    /**
     * @param CultureFeed_Uitpas_Event_CultureEvent $event
     * @return array
     */
    private function getUitpasPricesFromEvent(CultureFeed_Uitpas_Event_CultureEvent $event)
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
     * @param CultureFeed_Uitpas_Event_CultureEvent $event
     * @return string[]
     */
    private function getUitpasAdvantagesFromEvent(CultureFeed_Uitpas_Event_CultureEvent $event)
    {
        $advantages = [];

        if ($this->pointCollecting->isSatisfiedBy($event)) {
            $advantages[] = EventAdvantage::POINT_COLLECTING;
        }

        return $advantages;
    }

    /**
     * Get a list of formatted promotions
     *
     * @param  \CultureFeed_Uitpas_Event_CultureEvent $event
     * @return string[]
     */
    private function getUitpasPointsPromotionsFromEvent(\CultureFeed_Uitpas_Event_CultureEvent $event)
    {
        $promotions = [];
        $promotionQuery = $this->promotionQueryFactory->createForEvent($event);

        /**
 * @var \CultureFeed_PointsPromotion[] $promotionQueryResults
*/
        $promotionQueryResults = [];

        try {
            $promotionQueryResults = $this->uitpas->getPromotionPoints($promotionQuery)->objects;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error(
                    'Can\'t retrieve promotions for event with id:'.$event->cdbid,
                    ['exception' => $e]
                );
            }
        };

        foreach ($promotionQueryResults as $promotionsQueryResult) {
            if ($promotionsQueryResult->points === 1) {
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

    /**
     * @param CultureFeed_Uitpas_CardSystem      $cardSystem
     * @param CultureFeed_Uitpas_DistributionKey $key
     * @return array
     */
    private function getUitpasPricesFromDistributionKey(
        CultureFeed_Uitpas_CardSystem $cardSystem,
        CultureFeed_Uitpas_DistributionKey $key
    ) {
        $uitpasPrices = [];

        $tariffAsNumeric = is_integer($key->tariff) || is_float($key->tariff) ? $key->tariff : floatval($key->tariff);

        if ($this->kansenTariefForCurrentCardSystem->isSatisfiedBy($key)) {
            $uitpasPrices[] = [
                'price' => $tariffAsNumeric,
                'cardSystem' => $cardSystem->name,
                'forOtherCardSystems' => false
            ];
        }

        if ($this->kansenTariefForOtherCardSystems->isSatisfiedBy($key)) {
            $uitpasPrices[] = [
                'price' => $tariffAsNumeric,
                'cardSystem' => $cardSystem->name,
                'forOtherCardSystems' => true,
            ];
        }

        return $uitpasPrices;
    }
}
