<?php

namespace CultuurNet\UDB3\Http;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class CompositePsr7RequestAuthorizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_authorize_a_request_with_all_provided_authorizers()
    {
        $request = new Request('DELETE', 'http://foo.bar');
        $requestWithJwt = $request->withHeader('Authorization', 'Big jwt token');
        $requestWithJwtAndApiKey = $requestWithJwt->withHeader('X-Api-Key', 'Small api key');

        $jwtAuthorizer = $this->createMock(Psr7RequestAuthorizerInterface::class);
        $jwtAuthorizer->expects($this->once())
            ->method('authorize')
            ->with($request)
            ->willReturn($requestWithJwt);

        $apiKeyAuthorizer = $this->createMock(Psr7RequestAuthorizerInterface::class);
        $apiKeyAuthorizer->expects($this->once())
            ->method('authorize')
            ->with($requestWithJwt)
            ->willReturn($requestWithJwtAndApiKey);

        $compositePsr7RequestAuthorizer = new CompositePsr7RequestAuthorizer(
            $jwtAuthorizer,
            $apiKeyAuthorizer
        );

        $actualRequest = $compositePsr7RequestAuthorizer->authorize($request);

        $expectedRequest = new Request(
            'DELETE',
            'http://foo.bar',
            [
                'Authorization' => 'Big jwt token',
                'X-Api-Key' => 'Small api key',
            ]
        );

        $this->assertEquals($expectedRequest, $actualRequest);
    }
}
