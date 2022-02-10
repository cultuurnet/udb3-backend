<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\RequestTransformer;

use CultuurNet\UDB3\Model\ValueObject\Web\PortNumber;
use Psr\Http\Message\RequestInterface;

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
            $request->getUri()->withPort($this->port->toInteger())
        );
    }
}
