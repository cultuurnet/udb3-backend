<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

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
}
