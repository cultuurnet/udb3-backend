<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetCurrentUserRequestHandler implements RequestHandlerInterface
{
    private UserIdentityResolver $userIdentityResolver;

    private ?JsonWebToken $jwt;

    public function __construct(
        UserIdentityResolver $userIdentityResolver,
        ?JsonWebToken $jsonWebToken
    ) {
        $this->userIdentityResolver = $userIdentityResolver;
        $this->jwt = $jsonWebToken;
    }
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->jwt === null || $this->jwt->getType() === JsonWebToken::UIT_ID_V2_CLIENT_ACCESS_TOKEN) {
            throw ApiProblem::unauthorized(
                'Client access tokens are not supported on this endpoint because a user is required to return user info.'
            );
        }

        $userIdentity = $this->jwt->getUserIdentityDetails($this->userIdentityResolver);
        if (!($userIdentity instanceof UserIdentityDetails)) {
            throw ApiProblem::unauthorized('No user found for the given token.');
        }

        $userIdentityAsArray = $userIdentity->jsonSerialize();
        // Keep `id` and `nick` for backwards compatibility with older API clients
        $userIdentityAsArray['id'] = $userIdentity->getUserId();
        $userIdentityAsArray['nick'] = $userIdentity->getUserName();

        return new JsonLdResponse($userIdentityAsArray, 200);
    }
}
