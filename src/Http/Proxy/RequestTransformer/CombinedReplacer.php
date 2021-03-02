<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\RequestTransformer;

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
