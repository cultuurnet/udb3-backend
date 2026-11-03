<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HideAiGeneratedMiddlewareTest extends TestCase
{
    private HideAiGeneratedMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new HideAiGeneratedMiddleware();
    }

    /**
     * @test
     */
    public function it_removes_is_ai_generated_from_a_json_request_body(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withBodyFromString(Json::encode([
                'description' => 'An image',
                'isAiGenerated' => true,
                'images' => [
                    ['@id' => 'https://io.uitdatabank.dev/images/1', 'isAiGenerated' => false],
                ],
                'bookingInfo' => new \stdClass(),
            ]))
            ->build('PUT');

        $handler = new class () implements RequestHandlerInterface {
            public ?ServerRequestInterface $request = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;
                return new NoContentResponse();
            }
        };

        $this->middleware->process($request, $handler);

        $this->assertEquals(
            '{"description":"An image","images":[{"@id":"https:\/\/io.uitdatabank.dev\/images\/1"}],"bookingInfo":{}}',
            (string) $handler->request->getBody()
        );
    }

    /**
     * @test
     */
    public function it_removes_is_ai_generated_from_a_parsed_form_body(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withParsedBody([
                'description' => 'An image',
                'isAiGenerated' => 'true',
            ])
            ->build('POST');

        $handler = new class () implements RequestHandlerInterface {
            public ?ServerRequestInterface $request = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;
                return new NoContentResponse();
            }
        };

        $this->middleware->process($request, $handler);

        $this->assertEquals(['description' => 'An image'], $handler->request->getParsedBody());
    }

    /**
     * @test
     */
    public function it_does_not_change_a_request_without_is_ai_generated(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withBodyFromString('{"description":"An image"}')
            ->build('PUT');

        $handler = new class () implements RequestHandlerInterface {
            public ?ServerRequestInterface $request = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;
                return new NoContentResponse();
            }
        };

        $this->middleware->process($request, $handler);

        $this->assertSame($request, $handler->request);
    }

    /**
     * @test
     */
    public function it_does_not_change_an_invalid_json_request_body(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withBodyFromString('{"isAiGenerated": true,')
            ->build('PUT');

        $handler = new class () implements RequestHandlerInterface {
            public ?ServerRequestInterface $request = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;
                return new NoContentResponse();
            }
        };

        $this->middleware->process($request, $handler);

        $this->assertEquals('{"isAiGenerated": true,', (string) $handler->request->getBody());
    }

    /**
     * @test
     */
    public function it_removes_is_ai_generated_from_a_json_response_body(): void
    {
        $request = (new Psr7RequestBuilder())->build('GET');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new JsonResponse([
                    'mediaObject' => [
                        ['@id' => 'https://io.uitdatabank.dev/images/1', 'isAiGenerated' => true],
                    ],
                    'location' => [
                        'mediaObject' => [
                            ['@id' => 'https://io.uitdatabank.dev/images/2', 'isAiGenerated' => false],
                        ],
                    ],
                ]);
            }
        };

        $response = $this->middleware->process($request, $handler);

        $this->assertEquals(
            [
                'mediaObject' => [
                    ['@id' => 'https://io.uitdatabank.dev/images/1'],
                ],
                'location' => [
                    'mediaObject' => [
                        ['@id' => 'https://io.uitdatabank.dev/images/2'],
                    ],
                ],
            ],
            Json::decodeAssociatively((string) $response->getBody())
        );
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }
}
