<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\RequestTransformer;

use CultuurNet\UDB3\Model\ValueObject\Web\Hostname;
use Psr\Http\Message\RequestInterface;

class DomainReplacer implements RequestTransformerInterface
{
    private Hostname $hostname;

    public function __construct(Hostname $hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * @inheritdoc
     */
    public function transform(RequestInterface $request)
    {
        return $request->withUri(
            $request->getUri()->withHost($this->hostname->toString())
        );
    }
}
