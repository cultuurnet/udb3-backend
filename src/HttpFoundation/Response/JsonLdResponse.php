<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class JsonLdResponse extends JsonResponse
{
    public function __construct($data = null, $status = 200, $headers = [])
    {
        $headers += ['Content-Type' => 'application/ld+json'];

        parent::__construct(
            $data,
            $status,
            $headers
        );
    }
}
