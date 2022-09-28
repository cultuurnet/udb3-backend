<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsHeadersMiddlewareTest extends TestCase
{
    private CorsHeadersMiddleware $corsHeadersMiddleware;
    private RequestHandlerInterface $requestHandler;

    protected function setUp(): void
    {
        $this->corsHeadersMiddleware = new CorsHeadersMiddleware();
        $this->requestHandler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new NoContentResponse();
            }
        };
    }

    /**
     * @test
     */
    public function it_adds_default_cors_headers_to_every_response(): void
    {
        $givenRequest = (new Psr7RequestBuilder())->build('GET');

        $expectedHeaders = [
            'Allow' => ['GET,POST,PUT,PATCH,DELETE'],
            'Access-Control-Allow-Methods' => ['GET,POST,PUT,PATCH,DELETE'],
            'Access-Control-Allow-Credentials' => ['true'],
            'Access-Control-Allow-Origin' => ['*'],
            'Access-Control-Allow-Headers' => ['authorization,x-api-key'],
        ];

        $response = $this->corsHeadersMiddleware->process($givenRequest, $this->requestHandler);
        $actualHeaders = $response->getHeaders();

        $this->assertEquals($expectedHeaders, $actualHeaders);
    }

    /**
     * @test
     */
    public function it_allows_a_specific_origin_if_included_in_the_request(): void
    {
        $givenRequest = (new Psr7RequestBuilder())
            ->withHeader('Origin', 'www.uitdatabank.dev')
            ->build('GET');

        $expectedHeaders = [
            'Allow' => ['GET,POST,PUT,PATCH,DELETE'],
            'Access-Control-Allow-Methods' => ['GET,POST,PUT,PATCH,DELETE'],
            'Access-Control-Allow-Credentials' => ['true'],
            'Access-Control-Allow-Origin' => ['www.uitdatabank.dev'],
            'Access-Control-Allow-Headers' => ['authorization,x-api-key'],
        ];

        $response = $this->corsHeadersMiddleware->process($givenRequest, $this->requestHandler);
        $actualHeaders = $response->getHeaders();

        $this->assertEquals($expectedHeaders, $actualHeaders);
    }

    /**
     * @test
     */
    public function it_allows_specific_headers_if_included_in_the_request(): void
    {
        $givenRequest = (new Psr7RequestBuilder())
            ->withHeader('Access-Control-Request-Headers', 'authorization,x-api-key,x-mock-header')
            ->build('GET');

        $expectedHeaders = [
            'Allow' => ['GET,POST,PUT,PATCH,DELETE'],
            'Access-Control-Allow-Methods' => ['GET,POST,PUT,PATCH,DELETE'],
            'Access-Control-Allow-Credentials' => ['true'],
            'Access-Control-Allow-Origin' => ['*'],
            'Access-Control-Allow-Headers' => ['authorization,x-api-key,x-mock-header'],
        ];

        $response = $this->corsHeadersMiddleware->process($givenRequest, $this->requestHandler);
        $actualHeaders = $response->getHeaders();

        $this->assertEquals($expectedHeaders, $actualHeaders);
    }
}
