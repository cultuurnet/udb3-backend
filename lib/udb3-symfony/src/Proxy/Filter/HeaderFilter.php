<?php

namespace CultuurNet\UDB3\Symfony\Proxy\Filter;

use Psr\Http\Message\RequestInterface;
use ValueObjects\StringLiteral\StringLiteral;

class HeaderFilter implements FilterInterface
{
    /**
     * @var StringLiteral
     */
    private $header;

    /**
     * @var StringLiteral
     */
    private $expectedValue;

    public function __construct(StringLiteral $header, StringLiteral $expectedValue)
    {
        $this->header = $header;
        $this->expectedValue = $expectedValue;
    }
    
    public function matches(RequestInterface $request)
    {
        $value = new StringLiteral('');
        
        if ($request->getHeaderLine($this->header)) {
            $value = new StringLiteral($request->getHeaderLine($this->header));
        }
        
        return $this->expectedValue->sameValueAs($value);
    }
}
