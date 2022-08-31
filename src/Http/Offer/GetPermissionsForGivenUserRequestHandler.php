<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\UncacheableJsonResponse;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetPermissionsForGivenUserRequestHandler extends GetPermissionsRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $userId = $routeParameters->get('userId');

        $permissions = $this->getPermissions(
            $offerId,
            $userId
        );
        return new UncacheableJsonResponse($permissions, StatusCodeInterface::STATUS_OK);
    }
}
