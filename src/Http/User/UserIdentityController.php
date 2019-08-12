<?php

namespace CultuurNet\UDB3\Http\User;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\HttpFoundation\ApiProblemJsonResponse;
use CultuurNet\UDB3\Http\JsonLdResponse;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolverInterface;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class UserIdentityController
{
    /**
     * @var UserIdentityResolverInterface
     */
    private $userIdentityResolver;

    /**
     * @param UserIdentityResolverInterface $userIdentityResolver
     */
    public function __construct(
        UserIdentityResolverInterface $userIdentityResolver
    ) {
        $this->userIdentityResolver = $userIdentityResolver;
    }

    /**
     * @param string $userId
     * @return Response
     */
    public function getByUserId($userId)
    {
        $userId = new StringLiteral((string) $userId);

        $userIdentity = $this->userIdentityResolver->getUserById($userId);

        return $this->createUserIdentityResponse($userIdentity);
    }

    /**
     * @param string $emailAddress
     * @return Response
     */
    public function getByEmailAddress($emailAddress)
    {
        try {
            $emailAddress = new EmailAddress((string) $emailAddress);
        } catch (InvalidNativeArgumentException $e) {
            return $this->createUserNotFoundResponse();
        }

        $userIdentity = $this->userIdentityResolver->getUserByEmail($emailAddress);

        return $this->createUserIdentityResponse($userIdentity);
    }

    /**
     * @param UserIdentityDetails|null $userIdentityDetails
     * @return Response
     */
    private function createUserIdentityResponse(UserIdentityDetails $userIdentityDetails = null)
    {
        if (is_null($userIdentityDetails)) {
            return $this->createUserNotFoundResponse();
        }

        return (new JsonLdResponse())
            ->setData($userIdentityDetails)
            ->setPrivate();
    }

    /**
     * @return ApiProblemJsonResponse
     */
    private function createUserNotFoundResponse()
    {
        return new ApiProblemJsonResponse(
            new ApiProblem('User not found.')
        );
    }
}
