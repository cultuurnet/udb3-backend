<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\Filter;

use CultuurNet\UDB3\Http\Proxy\FilterPathRegex;
use Psr\Http\Message\RequestInterface;
use ValueObjects\StringLiteral\StringLiteral;

class PreflightFilter implements FilterInterface
{
    /**
     * @var FilterInterface
     */
    private $preflightFilter;

    public function __construct(FilterPathRegex $path, StringLiteral $method)
    {
        $this->preflightFilter = new AndFilter(
            [
                new PathFilter($path),
                new MethodFilter(new StringLiteral('OPTIONS')),
                new HeaderFilter(
                    new StringLiteral('Access-Control-Request-Method'),
                    $method
                ),
            ]
        );
    }

    public function matches(RequestInterface $request)
    {
        return $this->preflightFilter->matches($request);
    }
}
