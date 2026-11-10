<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\UiTPAS\Client\UiTPASClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SetCardSystemsOnEventRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private UiTPASClient&MockObject $uitpasClient;

    private SetCardSystemsOnEventRequestHandler $handler;

    protected function setUp(): void
    {
        $this->uitpasClient = $this->createMock(UiTPASClient::class);

        $this->handler = new SetCardSystemsOnEventRequestHandler($this->uitpasClient);
    }

    /**
     * @test
     */
    public function it_converts_the_card_system_ids_to_integers_before_passing_them_to_the_client(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';

        // identicalTo uses strict comparison, so this fails if the ids stay strings.
        $this->uitpasClient->expects($this->once())
            ->method('setCardSystemsForEvent')
            ->with($eventId, $this->identicalTo([3, 15]));

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withJsonBodyFromArray(['3', '15'])
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_allows_an_empty_list_of_card_system_ids(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';

        $this->uitpasClient->expects($this->once())
            ->method('setCardSystemsForEvent')
            ->with($eventId, []);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withJsonBodyFromArray([])
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     * @dataProvider invalidCardSystemIdsProvider
     */
    public function it_throws_on_card_system_ids_that_are_not_numeric(array $cardSystemIds): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';

        $this->uitpasClient->expects($this->never())
            ->method('setCardSystemsForEvent');

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withJsonBodyFromArray($cardSystemIds)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidDataWithDetail('Payload should be an array of card system ids'),
            fn () => $this->handler->handle($request),
        );
    }

    public static function invalidCardSystemIdsProvider(): array
    {
        return [
            'card system name' => [['UiTPAS Brugge']],
            'valid id and card system name' => [[3, 'UiTPAS Brugge']],
            'empty string' => [['']],
            'null' => [[null]],
            'object' => [[['id' => 3]]],
            'float' => [[3.5]],
        ];
    }

    /**
     * @test
     */
    public function it_throws_on_empty_body(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';

        $this->uitpasClient->expects($this->never())
            ->method('setCardSystemsForEvent');

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidDataWithDetail('Payload should be an array of card system ids'),
            fn () => $this->handler->handle($request),
        );
    }
}
