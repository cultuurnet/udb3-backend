<?php

namespace CultuurNet\UDB3\Http\Proxy\RequestTransformer;

use Psr\Http\Message\RequestInterface;
use ValueObjects\Web\PortNumber;

class PortReplacer implements RequestTransformerInterface
{
    /**
     * @var PortNumber
     */
    private $port;

    public function __construct(PortNumber $port)
    {
        $this->port = $port;
    }

    /**
     * @return RequestInterface
     */
    public function transform(RequestInterface $request)
    {
        return $request->withUri(
            $request->getUri()->withPort($this->port->toNative())
        );
    }
}
