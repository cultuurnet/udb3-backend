<?php

namespace CultuurNet\UDB3\Http;

use Psr\Http\Message\RequestInterface;

class PassthroughPsr7RequestAuthorizer implements Psr7RequestAuthorizerInterface
{
    /**
     * @inheritdoc
     */
    public function authorize(RequestInterface $request)
    {
        return $request;
    }
}
