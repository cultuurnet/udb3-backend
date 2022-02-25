<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\Filter;

use Psr\Http\Message\RequestInterface;
use CultuurNet\UDB3\StringLiteral;

class AcceptFilter implements FilterInterface
{
    public const ACCEPT = 'Accept';

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
