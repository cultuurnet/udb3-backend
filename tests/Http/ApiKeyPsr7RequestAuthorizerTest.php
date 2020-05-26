<?php

namespace CultuurNet\UDB3\Http;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class ApiKeyPsr7RequestAuthorizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_authorize_a_request_with_x_api_key()
    {
        $apiKey = new StringLiteral('adde5285-a48c-4f3a-bf30-0e75b7b888e8');

        $authorizer = new ApiKeyPsr7RequestAuthorizer($apiKey);

        $request = new Request('DELETE', 'http://foo.bar');
        $authorizedRequest = $authorizer->authorize($request);

        $this->assertEquals(
            'adde5285-a48c-4f3a-bf30-0e75b7b888e8',
            $authorizedRequest->getHeaderLine('X-Api-Key')
        );
    }
}
