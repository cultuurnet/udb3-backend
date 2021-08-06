<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Request\Body;

use Exception;

final class RequestBodyMissing extends Exception
{
    public function __construct()
    {
        parent::__construct('A request body is required but the given request body is empty.', 400);
    }
}
