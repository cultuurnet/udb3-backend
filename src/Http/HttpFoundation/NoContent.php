<?php

namespace CultuurNet\UDB3\Http\HttpFoundation;

use Symfony\Component\HttpFoundation\Response;

class NoContent extends Response
{
    public function __construct(array $headers = [])
    {
        parent::__construct('', 204, $headers);
    }
}
