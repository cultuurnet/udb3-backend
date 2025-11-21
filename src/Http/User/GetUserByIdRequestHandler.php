<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetUserByIdRequestHandler implements RequestHandlerInterface
{
    private UserIdentityResolver $userIdentityResolver;

    public function __construct(UserIdentityResolver $userIdentityResolver)
    {
        $this->userIdentityResolver = $userIdentityResolver;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = (new RouteParameters($request))->getUserId();

        $userIdentity = $this->userIdentityResolver->getUserById($userId);

        if (!($userIdentity instanceof UserIdentityDetails)) {
            throw ApiProblem::urlNotFound('No user found for the given user ID.');
        }

        return new JsonLdResponse($userIdentity);
    }
}
