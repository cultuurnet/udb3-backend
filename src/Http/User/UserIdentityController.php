<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Headers;
use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\Web\EmailAddress;

class UserIdentityController
{
    /**
     * @var UserIdentityResolver
     */
    private $userIdentityResolver;

    /**
     * @var JsonWebToken
     */
    private $jwt;

    public function __construct(
        UserIdentityResolver $userIdentityResolver,
        JsonWebToken $jsonWebToken
    ) {
        $this->userIdentityResolver = $userIdentityResolver;
        $this->jwt = $jsonWebToken;
    }

    public function getByEmailAddress(ServerRequestInterface $request): ResponseInterface
    {
        $emailAddressString = $request->getAttribute('emailAddress', '');
        try {
            $emailAddress = new EmailAddress($emailAddressString);
        } catch (InvalidNativeArgumentException $e) {
            return new ApiProblemJsonResponse(
                ApiProblem::invalidEmailAddress($emailAddressString)
            );
        }

        $userIdentity = $this->userIdentityResolver->getUserByEmail($emailAddress);

        if (!($userIdentity instanceof UserIdentityDetails)) {
            return new ApiProblemJsonResponse(
                ApiProblem::userNotFound('No user found for the given email address.')
            );
        }

        return (new JsonLdResponse($userIdentity));
    }

    public function getCurrentUser(): ResponseInterface
    {
        if ($this->jwt->getType() === JsonWebToken::V2_CLIENT_ACCESS_TOKEN) {
            return new ApiProblemJsonResponse(
                ApiProblem::tokenNotSupported('Client access tokens are not supported on this endpoint because a user is required to return user info.')
            );
        }

        $userIdentity = $this->jwt->getUserIdentityDetails($this->userIdentityResolver);
        if (!($userIdentity instanceof UserIdentityDetails)) {
            return new ApiProblemJsonResponse(
                ApiProblem::tokenNotSupported('No user found for the given token.')
            );
        }

        $userIdentityAsArray = $userIdentity->jsonSerialize();
        // Keep `id` and `nick` for backwards compatibility with older API clients
        $userIdentityAsArray['id'] = $userIdentity->getUserId();
        $userIdentityAsArray['nick'] = $userIdentity->getUserName();

        return new JsonLdResponse($userIdentityAsArray, 200, new Headers(['Cache-Control' => 'private']));
    }
}
