<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Response;

class NoContent extends Response
{
    public function __construct(array $headers = [])
    {
        parent::__construct('', 204, $headers);
    }
}
