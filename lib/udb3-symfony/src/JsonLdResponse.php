<?php

namespace CultuurNet\UDB3\Symfony;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @deprecated Use CultuurNet\UDB3\HttpFoundation\Response\JsonLdResponse instead.
 * @see https://github.com/cultuurnet/udb3-http-foundation/blob/master/src/Response/JsonLdResponse.php
 */
class JsonLdResponse extends JsonResponse
{
    public function __construct($data = null, $status = 200, $headers = array())
    {
        $headers += array(
          'Content-Type' => 'application/ld+json',
        );

        parent::__construct(
            $data,
            $status,
            $headers
        );
    }
}
