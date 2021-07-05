<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

use Crell\ApiProblem\ApiProblem;

/**
 * One class used to construct every possible API problem, so we have a definitive list (for documentation), and we can
 * more easily avoid almost-the-same duplicates.
 *
 * Ideally only this class would construct new ApiProblem instances, and always using specifically named methods.
 *
 * See https://datatracker.ietf.org/doc/html/rfc7807 for info on API problems.
 *
 * Most important points to keep in mind:
 * - Type should be a URI
 * - Title should always be the same for the used type
 * - The "about:blank" type can only be used if the error needs no further explanation than the status code
 *     (for example, 400 is too vague)
 * - If the "about:blank" type is used, the title should be the HTTP status phrase for the used status code
 *     (for example, "Internal Server Error" for 500)
 * - Avoid using "about:blank" in cases where extra documentation can be helpful
 *     (since the URIs will link to documentation on Stoplight)
 */
final class ApiProblems
{
    public static function internalServerError(string $detail = ''): ApiProblem
    {
        return (new ApiProblem())
            ->setType('about:blank')
            ->setTitle('Internal Server Error')
            ->setDetail($detail)
            ->setStatus(500);
    }

    public static function unauthorized(string $detail): ApiProblem
    {
        // Don't use about:blank as type here, even though we could, so we can make the URL point to documentation how
        // to fix this.
        return (new ApiProblem())
            ->setType('https://api.publiq.be/probs/auth/unauthorized')
            ->setTitle('Unauthorized')
            ->setDetail($detail)
            ->setStatus(401);
    }

    public static function forbidden(string $detail): ApiProblem
    {
        // Don't use about:blank as type here, even though we could, so we can make the URL point to documentation how
        // to fix this.
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

    public static function invalidEmailAddress(string $email): ApiProblem
    {
        return (new ApiProblem())
            ->setType('https://api.publiq.be/probs/uitdatabank/invalid-email-address')
            ->setTitle('Invalid email address')
            ->setDetail(
                sprintf('"%s" is not a valid email address', $email)
            )
            ->setStatus(400);
    }
}
