<?php

namespace CultuurNet\UDB3\Jwt;

/**
 * Exception for JwtParser errors
 */
class JwtParserException extends \InvalidArgumentException
{
    public function __construct($e)
    {
        parent::__construct($e->getMessage(), 403);
    }
}
