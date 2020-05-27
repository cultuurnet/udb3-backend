<?php

namespace CultuurNet\UDB3\Http;

use GuzzleHttp\Psr7\Request;
use Lcobucci\JWT\Signature;
use Lcobucci\JWT\Token as Jwt;
use PHPUnit\Framework\TestCase;

class JwtPsr7RequestAuthorizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_authorize_a_request_with_jwt_token(): void
    {
        $jwt = 'jwt.mock.example';

        $authorizer = new JwtPsr7RequestAuthorizer($jwt);

        $request = new Request('DELETE', 'http://foo.bar');
        $authorizedRequest = $authorizer->authorize($request);

        $this->assertEquals(
            'Bearer jwt.mock.example',
            $authorizedRequest->getHeaderLine('Authorization')
        );
    }
}
