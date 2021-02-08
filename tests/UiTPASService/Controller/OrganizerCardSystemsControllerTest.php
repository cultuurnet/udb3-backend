<?php

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_ResultSet;
use CultureFeed_Uitpas;
use CultureFeed_Uitpas_CardSystem;
use CultureFeed_Uitpas_DistributionKey;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrganizerCardSystemsControllerTest extends TestCase
{
    /**
     * @var CultureFeed_Uitpas|MockObject
     */
    private $uitPas;

    /**
     * @var OrganizerCardSystemsController
     */
    private $controller;

    public function setUp()
    {
        $this->uitPas = $this->createMock(CultureFeed_Uitpas::class);
        $this->controller = new OrganizerCardSystemsController($this->uitPas);
    }

    /**
     * @test
     */
    public function it_responds_with_a_list_of_card_systems_with_distribution_keys_for_a_given_organizer()
    {
        $organizerId = 'db93a8d0-331a-4575-a23d-2c78d4ceb925';

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
        $cardSystem2->id = 2;
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

        $resultSet = new CultureFeed_ResultSet();
        $resultSet->objects = $cardSystems;
        $resultSet->total = 2;

        $this->uitPas->expects($this->once())
            ->method('getCardSystemsForOrganizer')
            ->with($organizerId)
            ->willReturn($resultSet);

        $expectedResponseContent = (object) [
            'card-system-1' => (object) [
                'id' => 1,
                'name' => 'Card system 1',
                'distributionKeys' => (object) [
                    'distribution-key-1' => (object) [
                        'id' => 'distribution-key-1',
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
            $this->controller->get($organizerId)->getContent()
        );

        $this->assertEquals($expectedResponseContent, $actualResponseContent);
    }
}
