<?php

namespace CultuurNet\UDB3\Symfony\HttpFoundation;

use Symfony\Component\HttpFoundation\Response;

class NoContent extends Response
{
    public function __construct(array $headers = [])
    {
        parent::__construct('', 204, $headers);
    }
}
