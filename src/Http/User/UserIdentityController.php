<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Headers;

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
        } catch (InvalidArgumentException $e) {
            throw ApiProblem::urlNotFound(
                sprintf('"%s" is not a valid email address', $emailAddressString)
            );
        }

        $userIdentity = $this->userIdentityResolver->getUserByEmail($emailAddress);

        if (!($userIdentity instanceof UserIdentityDetails)) {
            throw ApiProblem::urlNotFound('No user found for the given email address.');
        }

        return (new JsonLdResponse($userIdentity));
    }

    public function getCurrentUser(): ResponseInterface
    {
        if ($this->jwt->getType() === JsonWebToken::V2_CLIENT_ACCESS_TOKEN) {
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

        return new JsonLdResponse($userIdentityAsArray, 200, new Headers(['Cache-Control' => 'private']));
    }
}
