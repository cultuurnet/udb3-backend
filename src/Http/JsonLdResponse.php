<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @deprecated Use CultuurNet\UDB3\HttpFoundation\Response\JsonLdResponse instead.
 * @see https://github.com/cultuurnet/udb3-http-foundation/blob/master/src/Response/JsonLdResponse.php
 */
class JsonLdResponse extends JsonResponse
{
    public function __construct($data = null, $status = 200, $headers = [])
    {
        $headers += [
          'Content-Type' => 'application/ld+json',
        ];

        parent::__construct(
            $data,
            $status,
            $headers
        );
    }
}
