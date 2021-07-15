<?php

namespace CultuurNet\UDB3\Jwt\Symfony\Authentication\Token;

/**
 * Access tokens are used to authenticate on APIs.
 * They contain no user details except for the user id. But they do contain the client id of the client that requested
 * the token, and whether they can use entry api or not.
 */
interface AccessToken extends Token
{
    public function getClientId(): string;
    public function canUseEntryApi(): bool;
}
