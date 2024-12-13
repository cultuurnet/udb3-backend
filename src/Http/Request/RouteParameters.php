<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class RouteParameters
{
    private array $attributes;

    public function __construct(ServerRequestInterface $request)
    {
        $this->attributes = $request->getAttributes();
    }

    public function get(string $parameterName): string
    {
        // The League router puts the parameters directly in the request attributes.
        if (isset($this->attributes[$parameterName])) {
            return rawurldecode($this->attributes[$parameterName]);
        }
        // The Silex router puts the parameters in a "_route_params" nested array.
        if (isset($this->attributes['_route_params'][$parameterName])) {
            return (string) $this->attributes['_route_params'][$parameterName];
        }
        throw new RuntimeException('Route parameter ' . $parameterName . ' not found in given ServerRequestInterface!');
    }

    public function getWithDefault(string $parameterName, string $default): string
    {
        if (!$this->has($parameterName)) {
            return $default;
        }

        return $this->get($parameterName) ?: $default;
    }

    public function has(string $parameterName): bool
    {
        // The League router puts the parameters directly in the request attributes.
        // The Silex router puts the parameters in a "_route_params" nested array.
        return isset($this->attributes[$parameterName]) || isset($this->attributes['_route_params'][$parameterName]);
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

    public function hasOwnershipId(): bool
    {
        return $this->has('ownershipId');
    }

    public function getOwnershipId(): string
    {
        return $this->get('ownershipId');
    }

    public function getLabelId(): string
    {
        return $this->get('labelId');
    }

    public function getProductionId(): string
    {
        return $this->get('productionId');
    }

    public function getMediaId(): string
    {
        return $this->get('mediaId');
    }

    public function hasMediaId(): bool
    {
        return $this->has('mediaId');
    }

    public function getRoleId(): Uuid
    {
        $roleId = $this->get('roleId');
        try {
            return new Uuid($roleId);
        } catch (InvalidArgumentException $exception) {
            throw ApiProblem::roleNotFound($roleId);
        }
    }

    public function hasRoleId(): bool
    {
        return $this->has('roleId');
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
            throw ApiProblem::urlNotFound('The provided language route parameter is not supported.');
        }
    }

    public function getLabelName(): LabelName
    {
        try {
            return new LabelName($this->get('labelName'));
        } catch (InvalidArgumentException $exception) {
            throw ApiProblem::urlNotFound('The label should match pattern: ^[^;]{2,255}$');
        }
    }

    public function getOfferType(): OfferType
    {
        $offerType = $this->get('offerType');
        if ($offerType === 'events') {
            return OfferType::event();
        }
        if ($offerType === 'places') {
            return OfferType::place();
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

    public function getUserId(): string
    {
        return $this->get('userId');
    }

    public function getPermission(): Permission
    {
        $permission = $this->get('permissionKey');
        try {
            return Permission::fromUpperCaseString($permission);
        } catch (InvalidArgumentException $ex) {
            throw ApiProblem::urlNotFound("Permission $permission is not a valid permission.");
        }
    }
}
