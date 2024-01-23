<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CheckTypeOfOfferMiddlewareTest extends TestCase
{
    private const EVENT_ID = 'event-f2ab-4317-a3bd-6f8bd656a9d1';
    private const PLACE_ID = 'place-f2ab-4317-a3bd-6f8bd656a9d1';

    /**
     * @dataProvider offerTypeProvider
     */
    public function testProcessMiddleware(array $attributes, bool $expectException): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getAttributes')
            ->with()
            ->willReturn($attributes);

        $handler = $this->createMock(RequestHandlerInterface::class);

        $placeRequested = $attributes['offerType'] === 'places';

        $placeRepository = $this->createMock(DocumentRepository::class);
        $placeRepository->expects($placeRequested ? $this->once() : $this->never())
            ->method('fetch')
            ->willReturnCallback(function (string $offerId) : JsonDocument {
                if ($offerId !== self::PLACE_ID) {
                    throw DocumentDoesNotExist::withId($offerId);
                }
                return new JsonDocument(self::PLACE_ID, '{}');
            });

        $eventRepository = $this->createMock(DocumentRepository::class);
        $eventRepository->expects($placeRequested ? $this->never() : $this->once())
            ->method('fetch')
            ->willReturnCallback(function (string $offerId) : JsonDocument {
                if ($offerId !== self::EVENT_ID) {
                    throw DocumentDoesNotExist::withId($offerId);
                }
                return new JsonDocument(self::EVENT_ID, '{}');
            });

        $middleware = new CheckTypeOfOfferMiddleware($placeRepository, $eventRepository);

        if($expectException) {
            $this->expectException(DocumentDoesNotExist::class);
        }

        $middleware->process($request, $handler);
    }

    public function offerTypeProvider(): array
    {
        return [
            'Looking for event on /events' => [['offerType' => 'events', 'offerId' => self::EVENT_ID], false],
            'Looking for place on /places' => [['offerType' => 'places', 'offerId' => self::PLACE_ID], false],
            'Looking for event on /places' => [['offerType' => 'places', 'offerId' => self::EVENT_ID], true],
            'Looking for place on /events' => [['offerType' => 'events', 'offerId' => self::PLACE_ID], true],
        ];
    }

    // And I wait for the place with url "%{placeUrl}" to be indexed
}
