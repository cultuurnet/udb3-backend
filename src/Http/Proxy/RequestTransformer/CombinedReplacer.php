<?php

namespace CultuurNet\UDB3\Symfony\Proxy\RequestTransformer;

use Psr\Http\Message\RequestInterface;

class CombinedReplacer implements RequestTransformerInterface
{
    /**
     * @var RequestTransformerInterface[]
     */
    private $transformers;

    public function __construct(array $transformers)
    {
        $this->transformers = $transformers;
    }

    /**
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function transform(RequestInterface $request)
    {
        foreach ($this->transformers as $transformer) {
            $request = $transformer->transform($request);
        }

        return $request;
    }
}
