<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblems;
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

    public function __construct(
        UserIdentityResolver $userIdentityResolver,
        JsonWebToken $jsonWebToken
    ) {
        $this->userIdentityResolver = $userIdentityResolver;
        $this->jwt = $jsonWebToken;
    }

    public function getByEmailAddress(ServerRequestInterface $request): JsonResponse
    {
        $emailAddressString = $request->getAttribute('emailAddress', '');
        try {
            $emailAddress = new EmailAddress($emailAddressString);
        } catch (InvalidNativeArgumentException $e) {
            return new ApiProblemJsonResponse(
                ApiProblems::invalidEmailAddress($emailAddressString)
            );
        }

        $userIdentity = $this->userIdentityResolver->getUserByEmail($emailAddress);

        if (!($userIdentity instanceof UserIdentityDetails)) {
            return new ApiProblemJsonResponse(
                ApiProblems::userNotFound('No user found for the given email address.')
            );
        }

        return (new JsonLdResponse($userIdentity));
    }

    public function getCurrentUser(): JsonResponse
    {
        if ($this->jwt->getType() === JsonWebToken::V2_CLIENT_ACCESS_TOKEN) {
            return new ApiProblemJsonResponse(
                ApiProblems::tokenNotSupported('Client access tokens are not supported on this endpoint because a user is required to return user info.')
            );
        }

        if ($this->jwt->containsUserIdentityDetails()) {
            return $this->createCurrentUserResponse($this->jwt->getUserIdentityDetails());
        }

        $userIdentity = $this->userIdentityResolver->getUserById(new StringLiteral($this->jwt->getExternalUserId()));

        if (!($userIdentity instanceof UserIdentityDetails)) {
            return new ApiProblemJsonResponse(
                ApiProblems::tokenNotSupported('No user found for the user id in the given token.')
            );
        }

        return $this->createCurrentUserResponse($userIdentity);
    }

    private function createCurrentUserResponse(UserIdentityDetails $userIdentityDetails): JsonLdResponse
    {
        $userIdentityAsArray = $userIdentityDetails->jsonSerialize();
        // Keep `id` and `nick` for backwards compatibility with older API clients
        $userIdentityAsArray['id'] = $userIdentityDetails->getUserId()->toNative();
        $userIdentityAsArray['nick'] = $userIdentityDetails->getUserName()->toNative();

        return new JsonLdResponse($userIdentityAsArray, 200, ['Cache-Control' => 'private']);
    }
}
