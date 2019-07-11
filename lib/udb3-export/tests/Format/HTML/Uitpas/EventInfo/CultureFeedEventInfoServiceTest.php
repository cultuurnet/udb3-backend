<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo;

use CultureFeed_ResultSet as ResultSet;
use CultureFeed_Uitpas as Uitpas;
use CultureFeed_Uitpas_CardSystem as CardSystem;
use CultureFeed_Uitpas_DistributionKey_Condition as Condition;
use CultureFeed_Uitpas_Event_Query_SearchEventsOptions as SearchEventsOptions;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey\DistributionKeyConditionFactory;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey\DistributionKeyFactory;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event\EventAdvantage;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event\EventFactory;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Promotion\PromotionQueryFactoryInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Class CultureFeedEventInfoServiceTest
 * @package CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo
 */
class CultureFeedEventInfoServiceTest extends TestCase
{
    /**
     * @var \CultureFeed_Uitpas|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $uitpas;

    /**
     * @var CultureFeedEventInfoService
     */
    protected $infoService;

    /**
     * @var PromotionQueryFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $promotionQueryFactory;

    public function setUp()
    {
        $this->promotionQueryFactory = $this->createMock(PromotionQueryFactoryInterface::class);

        $this->uitpas = $this->createMock(Uitpas::class);
        $this->infoService = new CultureFeedEventInfoService(
            $this->uitpas,
            $this->promotionQueryFactory
        );
    }

    /**
     * @test
     */
    public function it_can_return_multiple_prices_and_advantages()
    {
        // Create an event with a specific id and a point collecting advantage.
        $eventFactory = new EventFactory();
        $eventId = 'd1f0e71d-a9a8-4069-81fb-530134502c58';
        $event = $eventFactory->buildEventWithPoints(1);

        // Set multiple kansentarief discounts (prices).
        $distributionKeyFactory = new DistributionKeyFactory();
        $distributionKeyConditionFactory = new DistributionKeyConditionFactory();
        $distributionKeys = [];
        $distributionKeys[] = $distributionKeyFactory->buildKey(
            2.0,
            [
                $distributionKeyConditionFactory->buildCondition(
                    Condition::DEFINITION_KANSARM,
                    Condition::OPERATOR_IN,
                    Condition::VALUE_MY_CARDSYSTEM
                ),
            ]
        );
        $distributionKeys[] = $distributionKeyFactory->buildKey(
            "3.45",
            [
                $distributionKeyConditionFactory->buildCondition(
                    Condition::DEFINITION_KANSARM,
                    Condition::OPERATOR_IN,
                    Condition::VALUE_AT_LEAST_ONE_CARDSYSTEM
                ),
            ]
        );
        $distributionKeys[] = $distributionKeyFactory->buildKey(
            0.50,
            [
                $distributionKeyConditionFactory->buildCondition(
                    Condition::DEFINITION_KANSARM,
                    Condition::OPERATOR_IN,
                    Condition::VALUE_MY_CARDSYSTEM
                ),
            ]
        );

        // Store each kansentarief discount in a separate CardSystem, so we can
        // verify that discounts from the one CardSystem are not overwritten by
        // discounts from other CardSystem objects.
        $event->cardSystems = [];
        $cardSystemId = 0;
        foreach ($distributionKeys as $distributionKey) {
            $cardSystemId++;

            $cardSystem = new CardSystem();
            $cardSystem->id = $cardSystemId;
            $cardSystem->name = 'UiTPAS regio ' . $cardSystemId;
            $cardSystem->distributionKeys = [$distributionKey];

            $event->cardSystems[] = $cardSystem;
        }

        // We will be pretending to search on UiTPAS for this event object.
        $searchEvents = new SearchEventsOptions();
        $searchEvents->cdbid = $eventId;

        // We expect to receive the event object we just instantiated.
        $resultSet = new ResultSet();
        $resultSet->total = 1;
        $resultSet->objects = [$event];

        $promotion = new \CultureFeed_PointsPromotion();
        $promotion->points = 10;
        $promotion->title = 'free drink';

        $onePointPromotion = new \CultureFeed_PointsPromotion();
        $onePointPromotion->points = 1;
        $onePointPromotion->title = 'one point to rule them all';

        $promotionResultSet = new \CultureFeed_ResultSet(1, [$promotion, $onePointPromotion]);

        $this->uitpas->expects($this->once())
            ->method('searchEvents')
            ->with($searchEvents)
            ->willReturn($resultSet);

        $promotionsQuery = new \CultureFeed_Uitpas_Passholder_Query_SearchPromotionPointsOptions();

        $this->promotionQueryFactory->expects($this->once())
            ->method('createForEvent')
            ->with($event)
            ->willReturn($promotionsQuery);

        $this->uitpas->expects($this->once())
            ->method('getPromotionPoints')
            ->with($promotionsQuery)
            ->willReturn($promotionResultSet);

        // Request info for the event.
        $eventInfo = $this->infoService->getEventInfo($eventId);
        $prices = $eventInfo->getPrices();
        $advantages = $eventInfo->getAdvantages();
        $promotions = $eventInfo->getPromotions();

        // Make sure we get back the correct prices and advantages.
        $this->assertEquals(
            [
                [
                    'price' => 2,
                    'cardSystem' => 'UiTPAS regio 1',
                    'forOtherCardSystems' => false,
                ],
                [
                    'price' => 3.45,
                    'cardSystem' => 'UiTPAS regio 2',
                    'forOtherCardSystems' => true,
                ],
                [
                    'price' => 0.50,
                    'cardSystem' => 'UiTPAS regio 3',
                    'forOtherCardSystems' => false,
                ],
            ],
            $prices
        );

        $this->assertEquals(
            [
                EventAdvantage::POINT_COLLECTING(),
            ],
            $advantages
        );

        $this->assertEquals(
            [
                'free drink (10 ptn)',
                'one point to rule them all (1 pt)'
            ],
            $promotions
        );
    }

    /**
     * @test
     */
    public function it_logs_failures_occurring_when_retrieving_promotions()
    {
        // Create an event with a specific id.
        $eventFactory = new EventFactory();
        $event = $eventFactory->buildEventWithPoints(1);
        $event->cdbid = 'd1f0e71d-a9a8-4069-81fb-530134502c58';

        // We expect to receive the event object we just instantiated.
        $resultSet = new ResultSet();
        $resultSet->total = 1;
        $resultSet->objects = [$event];

        $this->uitpas->expects($this->any())
            ->method('searchEvents')
            ->willReturn($resultSet);

        $promotionsQuery = new \CultureFeed_Uitpas_Passholder_Query_SearchPromotionPointsOptions();

        $this->promotionQueryFactory->expects($this->any())
            ->method('createForEvent')
            ->with($event)
            ->willReturn($promotionsQuery);

        $this->uitpas->expects($this->any())
            ->method('getPromotionPoints')
            ->with($promotionsQuery)
            ->willThrowException(
                new \Exception('whatever exception')
            );

        // Assert that the exception that occurred was handled gracefully,
        // event info is still returned.
        $eventInfo = $this->infoService->getEventInfo($event->cdbid);
        $this->assertEquals(
            [
                EventAdvantage::POINT_COLLECTING(),
            ],
            $eventInfo->getAdvantages()
        );

        // Assert that when we attach a logger, event info is still returned and
        // the exception also gets logged.
        $testLogHandler = new TestHandler();
        $logger = new Logger('test', [$testLogHandler]);
        $this->infoService->setLogger($logger);
        $eventInfo = $this->infoService->getEventInfo($event->cdbid);
        $this->assertEquals(
            [
                EventAdvantage::POINT_COLLECTING(),
            ],
            $eventInfo->getAdvantages()
        );

        $this->assertTrue(
            $testLogHandler->hasError(
                'Can\'t retrieve promotions for event with id:' . $event->cdbid
            )
        );
    }
}
