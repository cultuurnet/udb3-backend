<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailinglist;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Mailinglist\Client\MailinglistClient;
use CultuurNet\UDB3\Mailinglist\Client\MailinglistSubscriptionFailed;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class SubscribeUserToMailinglistRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const EMAIL = 'test@publiq.be';
    private const MAILINGLIST_ID = 1;

    private SubscribeUserToMailinglistRequestHandler $handler;

    /**
     * @var MailinglistClient&MockObject
     */
    private $mailinglistClient;

    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->mailinglistClient = $this->createMock(MailinglistClient::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->handler = new SubscribeUserToMailinglistRequestHandler(
            $this->mailinglistClient,
            $logger
        );

        $this->request = (new Psr7RequestBuilder())
            ->withUriFromString('/mailinglist/' . self::MAILINGLIST_ID)
            ->withRouteParameter('mailingListId', (string) self::MAILINGLIST_ID)
            ->withJsonBodyFromArray(['email' => self::EMAIL])
            ->build('PUT');
    }

    /** @test */
    public function it_handles_subscribing(): void
    {
        $this->mailinglistClient->expects($this->once())
            ->method('subscribe')
            ->with(new EmailAddress(self::EMAIL), self::MAILINGLIST_ID);

        $response = $this->handler->handle($this->request);

        $this->assertEquals(StatusCodeInterface::STATUS_NO_CONTENT, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_mailinglist_client_throwing_exception(): void
    {
        $errorMessage = 'Failed to subscribe to newsletter"';

        $this->mailinglistClient->expects($this->once())
            ->method('subscribe')
            ->with(new EmailAddress(self::EMAIL), self::MAILINGLIST_ID)
            ->willThrowException(new MailinglistSubscriptionFailed($errorMessage));

        $this->assertCallableThrowsApiProblem(ApiProblem::failedToSubscribeToNewsletter($errorMessage), function () {
            $this->handler->handle($this->request);
        });
    }

    /** @test */
    public function it_handles_invalid_email_failure(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/mailinglist/' . self::MAILINGLIST_ID)
            ->withRouteParameter('mailingListId', (string) self::MAILINGLIST_ID)
            ->withJsonBodyFromArray(['email' => 'koen'])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(ApiProblem::bodyInvalidDataWithDetail('Given string is not a valid e-mail address.'), function () use ($request) {
            $this->handler->handle($request);
        });
    }

    /** @test */
    public function it_handles_no_email_failure(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/mailinglist/' . self::MAILINGLIST_ID)
            ->withRouteParameter('mailingListId', (string) self::MAILINGLIST_ID)
            ->withJsonBodyFromArray([])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(ApiProblem::requiredFieldMissing('email'), function () use ($request) {
            $this->handler->handle($request);
        });
    }

    /** @test */
    public function it_handles_invalid_body_failure(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/mailinglist/' . self::MAILINGLIST_ID)
            ->withRouteParameter('mailingListId', (string) self::MAILINGLIST_ID)
            ->withBodyFromString('<xml>bet you did not see that coming</xml>')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(ApiProblem::bodyInvalidSyntax('json'), function () use ($request) {
            $this->handler->handle($request);
        });
    }
}
