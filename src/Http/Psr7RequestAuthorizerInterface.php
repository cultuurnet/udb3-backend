<?php

namespace CultuurNet\UDB3\Http;

use Psr\Http\Message\RequestInterface;

interface Psr7RequestAuthorizerInterface
{
    /**
     * Authorizes a request, for example by adding an authorization header.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function authorize(RequestInterface $request);
}
