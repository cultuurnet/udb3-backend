<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use CultuurNet\UDB3\Offer\OfferType;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class RouteParameters
{
    private array $routeParameters;

    public function __construct(ServerRequestInterface $request)
    {
        $attributes = $request->getAttributes();
        $this->routeParameters = $attributes['_route_params'] ?? [];
    }

    public function get(string $parameterName): string
    {
        if (!isset($this->routeParameters[$parameterName])) {
            throw new RuntimeException('Route parameter ' . $parameterName . ' not found in given ServerRequestInterface!');
        }
        return (string) $this->routeParameters[$parameterName];
    }

    public function getEventId(): string
    {
        return $this->get('eventId');
    }

    public function getPlaceId(): string
    {
        return $this->get('placeId');
    }

    public function getOfferId(): string
    {
        return $this->get('offerId');
    }

    public function getOfferType(): OfferType
    {
        $offerType = $this->get('offerType');
        if ($offerType === 'events') {
            return OfferType::EVENT();
        }
        if ($offerType === 'places') {
            return OfferType::PLACE();
        }
        throw new RuntimeException('Unknown offer type ' . $offerType);
    }
}
