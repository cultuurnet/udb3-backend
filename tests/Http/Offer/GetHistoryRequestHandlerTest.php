<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

final class GetHistoryRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const EVENT_ID = '905dd0b7-57e2-4d0f-ad98-98070b8184a8';
    private const PLACE_ID = '09a917f5-c829-4af4-97cf-857d53c2cf0f';

    private GetHistoryRequestHandler $getHistoryRequestHandler;

    protected function setUp(): void
    {
        $eventHistoryDocumentRepository = new InMemoryDocumentRepository();
        $placeHistoryDocumentRepository = new InMemoryDocumentRepository();

        $eventHistoryDocumentRepository->save(
            new JsonDocument(self::EVENT_ID, Json::encode(self::getStoredHistoryData()))
        );
        $placeHistoryDocumentRepository->save(
            new JsonDocument(self::PLACE_ID, Json::encode(self::getStoredHistoryData()))
        );

        $this->getHistoryRequestHandler = new GetHistoryRequestHandler(
            $eventHistoryDocumentRepository,
            $placeHistoryDocumentRepository,
            true
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_the_current_user_is_not_allowed_to_access_event_history(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::EVENT_ID)
            ->build('GET');

        $requestHandler = new GetHistoryRequestHandler(
            new InMemoryDocumentRepository(),
            new InMemoryDocumentRepository(),
            false
        );

        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('Current user/client does not have enough permissions to access event history.'),
            fn () => $requestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_the_current_user_is_not_allowed_to_access_place_history(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'places')
            ->withRouteParameter('offerId', self::PLACE_ID)
            ->build('GET');

        $requestHandler = new GetHistoryRequestHandler(
            new InMemoryDocumentRepository(),
            new InMemoryDocumentRepository(),
            false
        );

        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('Current user/client does not have enough permissions to access place history.'),
            fn () => $requestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_the_requested_event_id_does_not_exist(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', 'a490615c-511e-4863-87c1-f4d34e8fd459')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::eventNotFound('a490615c-511e-4863-87c1-f4d34e8fd459'),
            fn () => $this->getHistoryRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_the_requested_place_id_does_not_exist(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'places')
            ->withRouteParameter('offerId', 'a490615c-511e-4863-87c1-f4d34e8fd459')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::placeNotFound('a490615c-511e-4863-87c1-f4d34e8fd459'),
            fn () => $this->getHistoryRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_returns_event_history_as_a_json_response(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::EVENT_ID)
            ->build('GET');

        $response = $this->getHistoryRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('content-type'));
        $this->assertEquals(self::getResponseHistoryData(), Json::decode($response->getBody()->getContents()));
    }

    /**
     * @test
     */
    public function it_returns_place_history_as_a_json_response(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', 'places')
            ->withRouteParameter('offerId', self::PLACE_ID)
            ->build('GET');

        $response = $this->getHistoryRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('content-type'));
        $this->assertEquals(self::getResponseHistoryData(), Json::decode($response->getBody()->getContents()));
    }

    private static function getStoredHistoryData(): array
    {
        return [
            (object) [
                'date' => '2021-08-26T16:54:38+00:00',
                'description' => 'Event aangemaakt in UiTdatabank',
                'author' => 'google-oauth2|108326107941342286958',
                'auth0ClientId' => 'JGJ3rAJLurRM9DHDE072zVhF3azl57mo',
                'auth0ClientName' => 'UiTdatabank',
                'api' => 'JSON-LD Import API',
            ],
            (object) [
                'date' => '2021-08-26T16:54:38+00:00',
                'description' => "Gepubliceerd (publicatiedatum: '2021-08-26T18:54:38+02:00')",
                'author' => 'google-oauth2|108326107941342286958',
                'auth0ClientId' => 'JGJ3rAJLurRM9DHDE072zVhF3azl57mo',
                'auth0ClientName' => 'UiTdatabank',
                'api' => 'JSON-LD Import API',
            ],
            (object) [
                'date' => '2021-08-26T16:54:39+00:00',
                'description' => 'Reservatie-info aangepast',
                'author' => 'google-oauth2|108326107941342286958',
                'auth0ClientId' => 'JGJ3rAJLurRM9DHDE072zVhF3azl57mo',
                'auth0ClientName' => 'UiTdatabank',
                'api' => 'JSON-LD Import API',
            ],
        ];
    }

    private static function getResponseHistoryData(): array
    {
        return [
            (object) [
                'date' => '2021-08-26T16:54:39+00:00',
                'description' => 'Reservatie-info aangepast',
                'author' => 'google-oauth2|108326107941342286958',
                'clientId' => 'JGJ3rAJLurRM9DHDE072zVhF3azl57mo',
                'auth0ClientId' => 'JGJ3rAJLurRM9DHDE072zVhF3azl57mo',
                'clientName' => 'UiTdatabank',
                'auth0ClientName' => 'UiTdatabank',
                'api' => 'JSON-LD Import API',
            ],
            (object) [
                'date' => '2021-08-26T16:54:38+00:00',
                'description' => "Gepubliceerd (publicatiedatum: '2021-08-26T18:54:38+02:00')",
                'author' => 'google-oauth2|108326107941342286958',
                'clientId' => 'JGJ3rAJLurRM9DHDE072zVhF3azl57mo',
                'auth0ClientId' => 'JGJ3rAJLurRM9DHDE072zVhF3azl57mo',
                'clientName' => 'UiTdatabank',
                'auth0ClientName' => 'UiTdatabank',
                'api' => 'JSON-LD Import API',
            ],
            (object) [
                'date' => '2021-08-26T16:54:38+00:00',
                'description' => 'Event aangemaakt in UiTdatabank',
                'author' => 'google-oauth2|108326107941342286958',
                'clientId' => 'JGJ3rAJLurRM9DHDE072zVhF3azl57mo',
                'auth0ClientId' => 'JGJ3rAJLurRM9DHDE072zVhF3azl57mo',
                'clientName' => 'UiTdatabank',
                'auth0ClientName' => 'UiTdatabank',
                'api' => 'JSON-LD Import API',
            ],
        ];
    }
}
