<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UWP;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\UWP\Exception\UwpApiFailure;
use CultuurNet\UDB3\UWP\Result\VerenigingsloketConnectionResult;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UwpApiConnectorTest extends TestCase
{
    private LoggerInterface|MockObject $logger;
    private MockHandler $mockHandler;
    private UwpApiConnector $uwpApiConnector;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mockHandler = new MockHandler();

        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        $this->uwpApiConnector = new UwpApiConnector(
            $client,
            'test-client-id',
            'test-client-secret',
            $this->logger
        );
    }

    public function testFetchVereningslokketConnectionForOrganizerReturnsResultWhenFound(): void
    {
        // Stack responses: first auth, then SPARQL query
        $this->mockHandler->append(
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'results' => [
                    'bindings' => [
                        [
                            'vcode' => ['value' => 'V123456'],
                            'vcode_url' => ['value' => 'https://www.verenigingsloket.be/nl/verenigingen/V123456'],
                        ],
                    ],
                ],
            ]))
        );

        $organizerId = new Uuid('12345678-1234-1234-1234-123456789012');
        $result = $this->uwpApiConnector->fetchVerenigingsloketConnectionForOrganizer($organizerId);

        $this->assertInstanceOf(VerenigingsloketConnectionResult::class, $result);
        $this->assertEquals('V123456', $result->getVcode());
        $this->assertEquals('https://www.verenigingsloket.be/nl/verenigingen/V123456', $result->getUrl());
    }

    public function testFetchVereningslokketConnectionForOrganizerReturnsNullWhenNotFound(): void
    {
        // Stack responses: first auth, then empty SPARQL results
        $this->mockHandler->append(
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'results' => [
                    'bindings' => [],
                ],
            ]))
        );

        $organizerId = new Uuid('12345678-1234-1234-1234-123456789012');
        $result = $this->uwpApiConnector->fetchVerenigingsloketConnectionForOrganizer($organizerId);

        $this->assertNull($result);
    }

    public function testFetchVereningslokketConnectionForOrganizerReturnsNullWhenMissingVcode(): void
    {
        // Stack responses: first auth, then incomplete SPARQL data
        $this->mockHandler->append(
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'results' => [
                    'bindings' => [
                        [
                            'vcode_url' => ['value' => 'https://www.verenigingsloket.be/nl/verenigingen/V123456'],
                            // Missing 'vcode' field
                        ],
                    ],
                ],
            ]))
        );

        $organizerId = new Uuid('12345678-1234-1234-1234-123456789012');
        $result = $this->uwpApiConnector->fetchVerenigingsloketConnectionForOrganizer($organizerId);

        $this->assertNull($result);
    }

    public function testFetchVereningslokketConnectionForOrganizerThrowsExceptionOnAuthFailure(): void
    {
        $this->mockHandler->append(
            new Response(401, [], json_encode(['error' => 'invalid_client']))
        );

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Failed to fetch access token from UWP', $this->anything());

        $this->expectException(UwpApiFailure::class);
        $this->expectExceptionMessage('No access token in response');

        $organizerId = new Uuid('12345678-1234-1234-1234-123456789012');
        $this->uwpApiConnector->fetchVerenigingsloketConnectionForOrganizer($organizerId);
    }

    public function testFetchVereningslokketConnectionForOrganizerUsesCorrectOrganizerId(): void
    {
        $organizerId = new Uuid('87654321-4321-4321-4321-210987654321');

        $this->mockHandler->append(
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'results' => [
                    'bindings' => [
                        [
                            'vcode' => ['value' => 'V654321'],
                            'vcode_url' => ['value' => 'https://www.verenigingsloket.be/nl/verenigingen/V654321'],
                        ],
                    ],
                ],
            ]))
        );

        $result = $this->uwpApiConnector->fetchVerenigingsloketConnectionForOrganizer($organizerId);

        $this->assertInstanceOf(VerenigingsloketConnectionResult::class, $result);
        $this->assertEquals('V654321', $result->getVcode());
        $this->assertEquals('https://www.verenigingsloket.be/nl/verenigingen/V654321', $result->getUrl());
    }
}
