<?php

namespace CultuurNet\UDB3\Http;

use Psr\Http\Message\RequestInterface;

class JwtPsr7RequestAuthorizer implements Psr7RequestAuthorizerInterface
{
    /**
     * @var string
     */
    private $jwt;

    /**
     * @param string $jwt
     */
    public function __construct(string $jwt)
    {
        $this->jwt = $jwt;
    }

    public function authorize(RequestInterface $request): RequestInterface
    {
        return $request->withHeader('Authorization', "Bearer {$this->jwt}");
    }
}
