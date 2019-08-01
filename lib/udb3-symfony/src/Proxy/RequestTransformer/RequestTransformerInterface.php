<?php

namespace CultuurNet\UDB3\Symfony\Proxy\RequestTransformer;

use Psr\Http\Message\RequestInterface;

interface RequestTransformerInterface
{
    /**
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function transform(RequestInterface $request);
}
