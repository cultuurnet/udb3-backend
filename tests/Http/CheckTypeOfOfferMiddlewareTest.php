<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class CheckTypeOfOfferMiddlewareTest extends TestCase
{
    private const EVENT_ID = 'event-f2ab-4317-a3bd-6f8bd656a9d1';
    private const PLACE_ID = 'place-f2ab-4317-a3bd-6f8bd656a9d1';

    /**
     * @dataProvider offerTypeProvider
     */
    public function testProcessMiddleware(string $offerType, string $offerId, bool $expectException): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString(sprintf('/%s/%s', $offerType, $offerId))
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', $offerId)
            ->build('GET');

        $handler = $this->createMock(RequestHandlerInterface::class);

        $placeRepository = new InMemoryDocumentRepository();
        $placeRepository->save(new JsonDocument(self::PLACE_ID, '{}'));

        $eventRepository = new InMemoryDocumentRepository();
        $eventRepository->save(new JsonDocument(self::EVENT_ID, '{}'));

        $middleware = new CheckTypeOfOfferMiddleware($placeRepository, $eventRepository);

        if ($expectException) {
            $this->expectException(DocumentDoesNotExist::class);
        }

        $middleware->process($request, $handler);
    }

    public function offerTypeProvider(): array
    {
        return [
            'Looking for event on /events' => ['events', self::EVENT_ID, false],
            'Looking for place on /places' => ['places', self::PLACE_ID, false],
            'Looking for event on /places' => ['places', self::EVENT_ID, true],
            'Looking for place on /events' => ['events', self::PLACE_ID, true],
        ];
    }
}
