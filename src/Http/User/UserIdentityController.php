<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Http\Message\ServerRequestInterface;
use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;
use Zend\Diactoros\Response\JsonResponse;

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

    /**
     * @var string
     */
    private $currentUserId;

    public function __construct(
        UserIdentityResolver $userIdentityResolver,
        JsonWebToken $jsonWebToken
    ) {
        $this->userIdentityResolver = $userIdentityResolver;
        $this->jwt = $jsonWebToken;
        $this->currentUserId = $jsonWebToken->getUserId();
    }

    public function getByEmailAddress(ServerRequestInterface $request): JsonResponse
    {
        try {
            $emailAddress = new EmailAddress($request->getAttribute('emailAddress'));
        } catch (InvalidNativeArgumentException $e) {
            return $this->createUserNotFoundResponse();
        }

        $userIdentity = $this->userIdentityResolver->getUserByEmail($emailAddress);

        if (!($userIdentity instanceof UserIdentityDetails)) {
            return $this->createUserNotFoundResponse();
        }

        return (new JsonLdResponse($userIdentity));
    }

    public function getCurrentUser(): JsonResponse
    {
        if ($this->jwt->getType() === JsonWebToken::V2_CLIENT_ACCESS_TOKEN) {
            return new ApiProblemJsonResponse(
                (new ApiProblem())
                    ->setType('https://api.publiq.be/probs/auth/token-type-not-supported')
                    ->setTitle('Token type not supported')
                    ->setDetail('Client access tokens are not supported on this endpoint because a user is required to return user info.')
                    ->setStatus(400)
            );
        }

        $userIdentity = $this->userIdentityResolver->getUserById(new StringLiteral($this->currentUserId));

        if (!($userIdentity instanceof UserIdentityDetails)) {
            return new ApiProblemJsonResponse(
                (new ApiProblem())
                    ->setType('https://api.publiq.be/probs/uitdatabank/user-not-found')
                    ->setTitle('User not found')
                    ->setDetail('No user found for the id in the given token.')
                    ->setStatus(400)
            );
        }

        $userIdentityAsArray = $userIdentity->jsonSerialize();
        // Keep `id` and `nick` for backwards compatibility with older API clients
        $userIdentityAsArray['id'] = $userIdentity->getUserId()->toNative();
        $userIdentityAsArray['nick'] = $userIdentity->getUserName()->toNative();

        return new JsonLdResponse($userIdentityAsArray, 200, ['Cache-Control' => 'private']);
    }

    private function createUserNotFoundResponse(): ApiProblemJsonResponse
    {
        return new ApiProblemJsonResponse(
            (new ApiProblem('User not found.'))
                ->setStatus(404)
        );
    }
}
