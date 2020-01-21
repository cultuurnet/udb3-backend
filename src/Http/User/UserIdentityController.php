<?php

namespace CultuurNet\UDB3\Http\User;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\HttpFoundation\Response\JsonLdResponse;
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

    public function getById(string $id): Response
    {
        $userIdentity = $this->userIdentityResolver->getUserById(new StringLiteral($id));

        if (!($userIdentity instanceof UserIdentityDetails)) {
            return $this->createUserNotFoundResponse();
        }

        return (new JsonLdResponse())
            ->setData($userIdentity)
            ->setPrivate();
    }

    public function getByEmailAddress(string $emailAddress): Response
    {
        try {
            $emailAddress = new EmailAddress($emailAddress);
        } catch (InvalidNativeArgumentException $e) {
            return $this->createUserNotFoundResponse();
        }

        $userIdentity = $this->userIdentityResolver->getUserByEmail($emailAddress);

        if (!($userIdentity instanceof UserIdentityDetails)) {
            return $this->createUserNotFoundResponse();
        }

        return (new JsonLdResponse())
            ->setData($userIdentity)
            ->setPrivate();
    }

    private function createUserNotFoundResponse(): ApiProblemJsonResponse
    {
        return new ApiProblemJsonResponse(
            (new ApiProblem('User not found.'))
                ->setStatus(404)
        );
    }
}
