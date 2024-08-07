<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultureFeed_Uitpas_CardSystem;
use CultureFeed_Uitpas_DistributionKey;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\UiTPASService\Controller\Response\CardSystemsJsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetCardSystemsFromOrganizerRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    /**
     * @var CultureFeed_Uitpas&MockObject
     */
    private $uitpas;

    private GetCardSystemsFromOrganizerRequestHandler $getCardSystemsFromOrganizerRequestHandler;

    protected function setUp(): void
    {
        $this->uitpas = $this->createMock(CultureFeed_Uitpas::class);

        $this->getCardSystemsFromOrganizerRequestHandler = new GetCardSystemsFromOrganizerRequestHandler($this->uitpas);
    }

    /**
     * @test
     */
    public function it_can_get_card_systems_of_an_organizer(): void
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

        $resultSet = new \CultureFeed_ResultSet();
        $resultSet->objects = $cardSystems;
        $resultSet->total = 2;

        $this->uitpas->expects($this->once())
            ->method('getCardSystemsForOrganizer')
            ->with($organizerId)
            ->willReturn($resultSet);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $organizerId)
            ->build('GET');

        $response = $this->getCardSystemsFromOrganizerRequestHandler->handle($request);

        $this->assertJsonResponse(new CardSystemsJsonResponse($cardSystems), $response);
    }
}
