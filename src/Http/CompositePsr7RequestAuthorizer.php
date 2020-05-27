<?php

namespace CultuurNet\UDB3\Http;

use Psr\Http\Message\RequestInterface;

class CompositePsr7RequestAuthorizer implements Psr7RequestAuthorizerInterface
{
    /**
     * @var Psr7RequestAuthorizerInterface[]
     */
    private $psr7RequestAuthorizers;

    /**
     * @param Psr7RequestAuthorizerInterface[] $psr7RequestAuthorizers
     */
    public function __construct(Psr7RequestAuthorizerInterface... $psr7RequestAuthorizers)
    {
        $this->psr7RequestAuthorizers = $psr7RequestAuthorizers;
    }

    /**
     * @inheritdoc
     */
    public function authorize(RequestInterface $request)
    {
        foreach ($this->psr7RequestAuthorizers as $psr7RequestAuthorizer) {
            $request = $psr7RequestAuthorizer->authorize($request);
        }

        return $request;
    }
}
