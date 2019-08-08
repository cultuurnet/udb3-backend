<?php

namespace CultuurNet\UDB3\Symfony\Proxy\Filter;

use Psr\Http\Message\RequestInterface;
use ValueObjects\StringLiteral\StringLiteral;

class MethodFilter implements FilterInterface
{
    /**
     * @var StringLiteral
     */
    private $method;

    public function __construct(StringLiteral $method)
    {
        $this->method = $method;
    }

    /**
     * @inheritdoc
     */
    public function matches(RequestInterface $request)
    {
        $method = $request->getMethod();
        return ($method === $this->method->toNative());
    }
}
