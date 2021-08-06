<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use Slim\Psr7\Headers;
use Slim\Psr7\Interfaces\HeadersInterface;

class JsonLdResponse extends JsonResponse
{
    public function __construct($data = null, $status = 200, ?HeadersInterface $headers = null)
    {
        if (!($headers instanceof HeadersInterface)) {
            $headers = new Headers();
        }

        $headers->setHeader('Content-Type', 'application/ld+json');

        parent::__construct(
            $data,
            $status,
            $headers
        );
    }
}
