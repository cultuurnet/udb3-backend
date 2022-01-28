<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\RequestTransformer;

use CultuurNet\UDB3\Model\ValueObject\Web\Domain;
use Psr\Http\Message\RequestInterface;

class DomainReplacer implements RequestTransformerInterface
{
    /**
     * @var Domain
     */
    private $domain;

    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @inheritdoc
     */
    public function transform(RequestInterface $request)
    {
        return $request->withUri(
            $request->getUri()->withHost($this->domain->toString())
        );
    }
}
