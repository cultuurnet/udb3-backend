<?php

namespace CultuurNet\UDB3\Http\Proxy\Filter;

use Psr\Http\Message\RequestInterface;
use ValueObjects\StringLiteral\StringLiteral;

class AcceptFilter implements FilterInterface
{
    const ACCEPT = 'Accept';

    /**
     * @var StringLiteral
     */
    private $accept;

    public function __construct(StringLiteral $accept)
    {
        $this->accept = $accept;
    }

    /**
     * @inheritdoc
     */
    public function matches(RequestInterface $request)
    {
        $accept = $request->getHeaderLine(self::ACCEPT);
        return ($accept === $this->accept->toNative());
    }
}
