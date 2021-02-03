<?php

namespace CultuurNet\UDB3\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class JsonLdResponse extends JsonResponse
{
    public function __construct($data = null, $status = 200, $headers = array())
    {
        $headers += ['Content-Type' => 'application/ld+json'];

        parent::__construct(
            $data,
            $status,
            $headers
        );
    }
}
