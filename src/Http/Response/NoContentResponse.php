<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use Slim\Psr7\Response;

final class NoContentResponse extends Response
{
    public function __construct()
    {
        parent::__construct(204);
    }
}
