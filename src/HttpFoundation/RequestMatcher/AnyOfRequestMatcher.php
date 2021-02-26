<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\RequestMatcher;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class AnyOfRequestMatcher implements RequestMatcherInterface
{
    /**
     * @var RequestMatcherInterface[]
     */
    private $requestMatchers;

    public function __construct()
    {
        $this->requestMatchers = [];
    }

    /**
     * @return AnyOfRequestMatcher
     */
    public function with(RequestMatcherInterface $requestMatcher)
    {
        $c = clone $this;
        $c->requestMatchers[] = $requestMatcher;
        return $c;
    }

    /**
     * @return bool
     */
    public function matches(Request $request)
    {
        foreach ($this->requestMatchers as $requestMatcher) {
            if ($requestMatcher->matches($request)) {
                return true;
            }
        }
        return false;
    }
}
