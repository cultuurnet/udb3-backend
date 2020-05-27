<?php

namespace CultuurNet\UDB3\Http;

use Psr\Http\Message\RequestInterface;
use ValueObjects\StringLiteral\StringLiteral;

class ApiKeyPsr7RequestAuthorizer implements Psr7RequestAuthorizerInterface
{
    /**
     * @var StringLiteral
     */
    private $apiKey;

    /**
     * @param StringLiteral $apiKey
     */
    public function __construct(StringLiteral $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @inheritdoc
     */
    public function authorize(RequestInterface $request)
    {
        return $request->withHeader("X-Api-Key", "{$this->apiKey}");
    }
}
