<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Http\Message\ServerRequestInterface;
use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\Web\EmailAddress;
use Zend\Diactoros\Response\JsonResponse;

class UserIdentityController
{
    /**
     * @var UserIdentityResolver
     */
    private $userIdentityResolver;


    public function __construct(
        UserIdentityResolver $userIdentityResolver
    ) {
        $this->userIdentityResolver = $userIdentityResolver;
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

    private function createUserNotFoundResponse(): ApiProblemJsonResponse
    {
        return new ApiProblemJsonResponse(
            (new ApiProblem('User not found.'))
                ->setStatus(404)
        );
    }
}
