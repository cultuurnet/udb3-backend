<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

use Crell\ApiProblem\ApiProblem;

/**
 * One class used to construct every possible API problem, so we have a definitive list (for documentation), and we can
 * more easily avoid almost-the-same duplicates.
 */
final class ApiProblems
{
    public static function unauthorized(string $detail): ApiProblem
    {
        return (new ApiProblem())
            ->setType('https://api.publiq.be/probs/auth/unauthorized')
            ->setTitle('Unauthorized')
            ->setDetail($detail)
            ->setStatus(401);
    }

    public static function forbidden(string $detail): ApiProblem
    {
        return (new ApiProblem())
            ->setType('https://api.publiq.be/probs/auth/forbidden')
            ->setTitle('Forbidden')
            ->setDetail($detail)
            ->setStatus(403);
    }

    public static function tokenNotSupported(string $detail): ApiProblem
    {
        return (new ApiProblem())
            ->setType('https://api.publiq.be/probs/auth/token-not-supported')
            ->setTitle('Token not supported')
            ->setDetail($detail)
            ->setStatus(400);
    }

    public static function userNotFound(string $detail): ApiProblem
    {
        return (new ApiProblem())
            ->setType('https://api.publiq.be/probs/uitdatabank/user-not-found')
            ->setTitle('User not found')
            ->setDetail($detail)
            ->setStatus(400);
    }
}
