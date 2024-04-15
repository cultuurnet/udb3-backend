<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailinglist;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Mailinglist\Client\MailinglistClient;
use CultuurNet\UDB3\Mailinglist\Client\MailinglistSubscriptionFailed;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class SubscribeUserToMailinglistRequestHandlerTest extends TestCase
{
    private SubscribeUserToMailinglistRequestHandler $handler;

    /**
     * @var MockObject|LoggerInterface
     */
    private $mailinglistClient;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->mailinglistClient = $this->createMock(MailinglistClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new SubscribeUserToMailinglistRequestHandler(
            $this->mailinglistClient,
            $this->logger
        );

        $email = urlencode('test@publiq.be');
        $mailingListId = '1';

        $this->request = (new Psr7RequestBuilder())
            ->withUriFromString('/mailinglist/{$email}/{$mailingListId}')
            ->withRouteParameter('email', $email)
            ->withRouteParameter('mailingListId', $mailingListId)
            ->build('PUT');
    }

    public function testHandleSuccess(): void
    {
        $this->mailinglistClient->expects($this->once())
            ->method('subscribe')
            ->with('test@publiq.be', 1);

        $response = $this->handler->handle($this->request);

        $this->assertEquals(['status' => 'ok'], json_decode($response->getBody()->getContents(), true));
    }

    public function testHandleFailure(): void
    {
        $errorMessage = 'Failed to subscribe';

        $this->mailinglistClient->expects($this->once())
            ->method('subscribe')
            ->with('test@publiq.be', 1)
            ->willThrowException(new MailinglistSubscriptionFailed($errorMessage));

        $this->logger->expects($this->once())
            ->method('error')
            ->with("Failed to subscribe to newsletter: $errorMessage");

        $response = $this->handler->handle($this->request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(['status' => $errorMessage], json_decode($response->getBody()->getContents(), true));
        $this->assertEquals(400, $response->getStatusCode());
    }
}
