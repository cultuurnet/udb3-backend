<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Media;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class IgnoreIsAiGeneratedMiddlewareTest extends TestCase
{
    private IgnoreIsAiGeneratedMiddleware $middleware;

    private ?ServerRequestInterface $handledRequest = null;

    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->middleware = new IgnoreIsAiGeneratedMiddleware();

        $this->handler = new class ($this) implements RequestHandlerInterface {
            private IgnoreIsAiGeneratedMiddlewareTest $test;

            public function __construct(IgnoreIsAiGeneratedMiddlewareTest $test)
            {
                $this->test = $test;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->test->setHandledRequest($request);
                return new NoContentResponse();
            }
        };
    }

    public function setHandledRequest(ServerRequestInterface $request): void
    {
        $this->handledRequest = $request;
    }

    /**
     * @test
     */
    public function it_removes_is_ai_generated_from_a_json_body(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'description' => 'Een afbeelding',
                'isAiGenerated' => true,
            ])
            ->build('POST');

        $this->middleware->process($request, $this->handler);

        $this->assertEquals(
            (object) ['description' => 'Een afbeelding'],
            Json::decode((string) $this->handledRequest->getBody())
        );
    }

    /**
     * @test
     */
    public function it_removes_nested_is_ai_generated_from_a_json_body(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withBodyFromString(
                '{"mediaObject":[{"@id":"https://io.uitdatabank.dev/images/1","isAiGenerated":true},{"@id":"https://io.uitdatabank.dev/images/2"}],"labels":[],"bookingInfo":{}}'
            )
            ->build('PUT');

        $this->middleware->process($request, $this->handler);

        $this->assertEquals(
            '{"mediaObject":[{"@id":"https:\/\/io.uitdatabank.dev\/images\/1"},{"@id":"https:\/\/io.uitdatabank.dev\/images\/2"}],"labels":[],"bookingInfo":{}}',
            (string) $this->handledRequest->getBody()
        );
    }

    /**
     * @test
     */
    public function it_removes_is_ai_generated_from_form_data(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withParsedBody([
                'description' => 'Een afbeelding',
                'isAiGenerated' => 'true',
            ])
            ->build('POST');

        $this->middleware->process($request, $this->handler);

        $this->assertEquals(['description' => 'Een afbeelding'], $this->handledRequest->getParsedBody());
    }

    /**
     * @test
     */
    public function it_keeps_a_body_without_is_ai_generated_untouched(): void
    {
        $body = '{"description":"Een afbeelding met é"}';
        $request = (new Psr7RequestBuilder())
            ->withBodyFromString($body)
            ->build('POST');

        $this->middleware->process($request, $this->handler);

        $this->assertSame($request, $this->handledRequest);
        $this->assertEquals($body, (string) $this->handledRequest->getBody());
    }

    /**
     * @test
     */
    public function it_keeps_an_invalid_json_body_untouched(): void
    {
        $body = '{"isAiGenerated": true';
        $request = (new Psr7RequestBuilder())
            ->withBodyFromString($body)
            ->build('POST');

        $this->middleware->process($request, $this->handler);

        $this->assertEquals($body, (string) $this->handledRequest->getBody());
    }
}
