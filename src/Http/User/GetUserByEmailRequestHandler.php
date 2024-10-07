<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\User;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidEmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetUserByEmailRequestHandler implements RequestHandlerInterface
{
    private UserIdentityResolver $userIdentityResolver;

    public function __construct(UserIdentityResolver $userIdentityResolver)
    {
        $this->userIdentityResolver = $userIdentityResolver;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $emailAddressString = (new RouteParameters($request))->get('email');

        try {
            $emailAddress = new EmailAddress($emailAddressString);
        } catch (InvalidEmailAddress $e) {
            throw ApiProblem::urlNotFound(
                sprintf('"%s" is not a valid email address', $emailAddressString)
            );
        }

        $userIdentity = $this->userIdentityResolver->getUserByEmail($emailAddress);

        if (!($userIdentity instanceof UserIdentityDetails)) {
            throw ApiProblem::urlNotFound('No user found for the given email address.');
        }

        return new JsonLdResponse($userIdentity);
    }
}
