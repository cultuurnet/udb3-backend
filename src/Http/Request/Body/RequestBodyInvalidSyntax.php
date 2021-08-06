<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Exception;

final class RequestBodyInvalidSyntax extends Exception
{
    public static function invalidJson(): self
    {
        return new self('The given request body could not be parsed as JSON.', 400);
    }
}
