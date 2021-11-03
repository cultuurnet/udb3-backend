<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\OfferType;
use InvalidArgumentException;
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

    public function has(string $parameterName): bool
    {
        return isset($this->routeParameters[$parameterName]);
    }

    public function getEventId(): string
    {
        return $this->get('eventId');
    }

    public function hasEventId(): bool
    {
        return $this->has('eventId');
    }

    public function getPlaceId(): string
    {
        return $this->get('placeId');
    }

    public function hasPlaceId(): bool
    {
        return $this->has('placeId');
    }

    public function getOfferId(): string
    {
        return $this->get('offerId');
    }

    public function hasOfferId(): bool
    {
        return $this->has('offerId');
    }

    public function hasOrganizerId(): bool
    {
        return $this->has('organizerId');
    }

    public function getOrganizerId(): string
    {
        return $this->get('organizerId');
    }

    public function hasLanguage(): bool
    {
        return $this->has('language');
    }

    /**
     * There are 3 possible scenarios:
     *  1. The given language parameter is correct, the given language is returned
     *  2. The given language parameter is incorrect, an ApiProblem is thrown
     *  3. The language parameter is missing (for a deprecated route), the language nl is returned
     */
    public function getLanguage(): Language
    {
        if (!$this->hasLanguage()) {
            return new Language('nl');
        }

        try {
            return new Language($this->get('language'));
        } catch (InvalidArgumentException $exception) {
            throw ApiProblem::pathParameterInvalid('The provided language route parameter is not supported.');
        }
    }

    public function getLabelName(): LabelName
    {
        try {
            return new LabelName($this->get('labelName'));
        } catch (InvalidArgumentException $exception) {
            throw ApiProblem::pathParameterInvalid('The label should match pattern: ^[^;]{2,255}$');
        }
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

    public function hasOfferType(): bool
    {
        // Do not use has() because that does not account for unknown offer types.
        try {
            $this->getOfferType();
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }
}
