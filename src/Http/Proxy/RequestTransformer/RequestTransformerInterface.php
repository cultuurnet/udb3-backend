<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\RequestTransformer;

use Psr\Http\Message\RequestInterface;

interface RequestTransformerInterface
{
    /**
     * @return RequestInterface
     */
    public function transform(RequestInterface $request);
}
