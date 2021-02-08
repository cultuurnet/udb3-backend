<?php

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultureFeed_Uitpas_CardSystem;
use CultureFeed_Uitpas_DistributionKey;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class EventCardSystemsControllerTest extends TestCase
{
    /**
     * @var CultureFeed_Uitpas|MockObject
     */
    private $uitpas;

    /**
     * @var EventCardSystemsController
     */
    private $controller;

    protected function setUp()
    {
        $this->uitpas = $this->createMock(CultureFeed_Uitpas::class);
        $this->controller = new EventCardSystemsController($this->uitpas);
    }

    /**
     * @test
     */
    public function it_can_get_card_systems_of_an_event()
    {
        $eventId = 'db93a8d0-331a-4575-a23d-2c78d4ceb925';

        $cardSystem1 = new CultureFeed_Uitpas_CardSystem();
        $cardSystem1->id = 1;
        $cardSystem1->name = 'Card system 1';

        $distributionKey1 = new CultureFeed_Uitpas_DistributionKey();
        $distributionKey1->id = 1;
        $distributionKey1->name = 'Distribution key 1';

        $distributionKey2 = new CultureFeed_Uitpas_DistributionKey();
        $distributionKey2->id = 2;
        $distributionKey2->name = 'Distribution key 2';

        $cardSystem1->distributionKeys = [
            $distributionKey1,
            $distributionKey2,
        ];

        $cardSystem2 = new CultureFeed_Uitpas_CardSystem();
        $cardSystem2->id = 3;
        $cardSystem2->name = 'Card system 2';

        $distributionKey3 = new CultureFeed_Uitpas_DistributionKey();
        $distributionKey3->id = 3;
        $distributionKey3->name = 'Distribution key 3';

        $distributionKey4 = new CultureFeed_Uitpas_DistributionKey();
        $distributionKey4->id = 4;
        $distributionKey4->name = 'Distribution key 4';

        $cardSystem2->distributionKeys = [
            $distributionKey3,
            $distributionKey4,
        ];

        $cardSystems = [
            $cardSystem1,
            $cardSystem2,
        ];

        $resultSet = new \CultureFeed_ResultSet();
        $resultSet->objects = $cardSystems;
        $resultSet->total = 2;

        $this->uitpas->expects($this->once())
            ->method('getCardSystemsForEvent')
            ->with($eventId)
            ->willReturn($resultSet);

        $expectedResponseContent = (object) [
            'card-system-1' => (object) [
                'id' => 1,
                'name' => 'Card system 1',
                'distributionKeys' => (object) [
                    'distribution-key-1' => (object) [
                        'id' => 1,
                        'name' => 'Distribution key 1',
                    ],
                    'distribution-key-2' => (object) [
                        'id' => 2,
                        'name' => 'Distribution key 2',
                    ],
                ],
            ],
            'card-system-2' => (object) [
                'id' => 2,
                'name' => 'Card system 2',
                'distributionKeys' => (object) [
                    'distribution-key-3' => (object) [
                        'id' => 3,
                        'name' => 'Distribution key 3',
                    ],
                    'distribution-key-4' => (object) [
                        'id' => 4,
                        'name' => 'Distribution key 4',
                    ],
                ],
            ],
        ];

        $actualResponseContent = json_decode(
            $this->controller->get($eventId)->getContent()
        );

        $this->assertEquals($expectedResponseContent, $actualResponseContent);
    }

    /**
     * @test
     */
    public function it_can_set_a_list_of_card_systems_to_an_event()
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemIds = ['3', '15'];

        $request = new Request([], [], [], [], [], [], json_encode($cardSystemIds));

        $this->uitpas->expects($this->once())
            ->method('setCardSystemsForEvent')
            ->with($eventId, $cardSystemIds)
            ->willReturn(null);

        $response = $this->controller->set($eventId, $request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_an_error_response_if_the_list_of_card_system_ids_is_not_an_array()
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemIds = 3;

        $request = new Request([], [], [], [], [], [], json_encode($cardSystemIds));

        $this->uitpas->expects($this->never())
            ->method('setCardSystemsForEvent')
            ->willReturn(null);

        $response = $this->controller->set($eventId, $request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_can_add_a_card_system_with_an_automatic_distribution_key_to_an_event()
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemId = '15';

        $this->uitpas->expects($this->once())
            ->method('addCardSystemToEvent')
            ->with($eventId, $cardSystemId)
            ->willReturn(null);

        $response = $this->controller->add($eventId, $cardSystemId);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_can_add_a_card_system_with_a_manual_distribution_key_to_an_event()
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemId = '27';
        $distributionKey = '1';

        $this->uitpas->expects($this->once())
            ->method('addCardSystemToEvent')
            ->with($eventId, $cardSystemId, $distributionKey)
            ->willReturn(null);

        $response = $this->controller->add($eventId, $cardSystemId, $distributionKey);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_can_remove_a_card_system_from_an_event()
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemId = '15';

        $this->uitpas->expects($this->once())
            ->method('deleteCardSystemFromEvent')
            ->with($eventId, $cardSystemId)
            ->willReturn(null);

        $response = $this->controller->delete($eventId, $cardSystemId);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
