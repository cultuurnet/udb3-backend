<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Verenigingsloket;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Verenigingsloket\Exception\VerenigingsloketApiFailure;
use CultuurNet\UDB3\Verenigingsloket\Result\VerenigingsloketConnectionResult;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class VerenigingsloketApiRepositoryTest extends TestCase
{
    private Uuid $organizerId;
    private VerenigingsloketApiRepository $apiConnector;

    private ClientInterface|MockObject $httpClient;
    private MockHandler $mockHandler;

    public function setUp(): void
    {
        $this->mockHandler = new MockHandler();

        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->httpClient = new Client(['handler' => $handlerStack]);
        $this->apiConnector = new VerenigingsloketApiRepository(
            $this->httpClient,
            'https://publiq.be/',
            'supersecret-api-key'
        );
        $this->organizerId = new Uuid('123e4567-e89b-12d3-a456-426614174000');
    }

    public function test_fetchVerenigingsloketConnectionForOrganizer_returns_connection_result_on_success(): void
    {
        $responseBody = json_encode([
            'member' => [
                [
                    'vCode' => 'VCODE123',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/ld+json'], $responseBody)
        );

        $result = $this->apiConnector->fetchVerenigingsloketConnectionForOrganizer($this->organizerId);

        $this->assertInstanceOf(VerenigingsloketConnectionResult::class, $result);
        $this->assertEquals('VCODE123', $result->getVcode());
        $this->assertEquals('https://publiq.be/VCODE123', $result->getUrl());
    }

    public function test_fetchVerenigingsloketConnectionForOrganizer_returns_null_on_invalid_json(): void
    {
        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/ld+json'], 'invalid json')
        );

        $result = $this->apiConnector->fetchVerenigingsloketConnectionForOrganizer($this->organizerId);

        $this->assertNull($result);
    }

    public function test_fetchVerenigingsloketConnectionForOrganizer_throws_exception_on_api_unavailable(): void
    {
        $this->mockHandler->append(
            new ConnectException('Connection failed', new Request('GET', 'api/relations'))
        );

        $this->expectException(VerenigingsloketApiFailure::class);
        $this->expectExceptionMessage('Verenigingsloket API is unavailable: Connection failed');

        $this->apiConnector->fetchVerenigingsloketConnectionForOrganizer($this->organizerId);
    }

    public function test_fetchVerenigingsloketConnectionForOrganizer_throws_exception_on_http_error(): void
    {
        $this->mockHandler->append(
            new Response(500, ['Content-Type' => 'application/json'], 'Internal Server Error')
        );

        $this->expectException(VerenigingsloketApiFailure::class);
        $this->expectExceptionMessage('Verenigingsloket API request failed: HTTP 500');

        $this->apiConnector->fetchVerenigingsloketConnectionForOrganizer($this->organizerId);
    }

    public function test_fetchVerenigingsloketConnectionForOrganizer_returns_null_on_empty_response(): void
    {
        $responseBody = json_encode([
            'member' => [],
        ], JSON_THROW_ON_ERROR);

        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/ld+json'], $responseBody)
        );

        $result = $this->apiConnector->fetchVerenigingsloketConnectionForOrganizer($this->organizerId);

        $this->assertNull($result);
    }

    public function test_fetchVerenigingsloketConnectionForOrganizer_handles_missing_vcode(): void
    {
        $responseBody = json_encode([
            'member' => [
                [
                    'someOtherField' => 'value',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/ld+json'], $responseBody)
        );

        $result = $this->apiConnector->fetchVerenigingsloketConnectionForOrganizer($this->organizerId);

        $this->assertNull($result);
    }
}
