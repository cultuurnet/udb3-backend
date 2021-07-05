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
    // The "about:blank" URI [RFC6694], when used as a problem type,
    // indicates that the problem has no additional semantics beyond that of
    // the HTTP status code.
    //
    // When "about:blank" is used, the title SHOULD be the same as the
    // recommended HTTP status phrase for that code (e.g., "Not Found" for
    // 404, and so on), although it MAY be localized to suit client
    // preferences (expressed with the Accept-Language request header).
    //
    // @see https://datatracker.ietf.org/doc/html/rfc7807#section-4.2
    private const TYPE_BLANK = 'about:blank';

    public static function internalServerError(string $detail = ''): ApiProblem
    {
        return (new ApiProblem())
            ->setType(self::TYPE_BLANK)
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
}
