<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailinglist;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Mailinglist\Client\MailinglistClient;
use CultuurNet\UDB3\Mailinglist\Client\MailinglistSubscriptionFailed;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response;

class SubscribeUserToMailinglistRequestHandler implements RequestHandlerInterface
{
    private MailinglistClient $client;
    private LoggerInterface $logger;

    public function __construct(MailinglistClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @throws ApiProblem
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $mailingListId = (new RouteParameters($request))->get('mailingListId');

        try {
            $body = Json::decodeAssociatively($request->getBody()->getContents());
        } catch (JsonException $e) {
            throw ApiProblem::bodyInvalidSyntax('json');
        }

        if (empty($body['email'])) {
            throw ApiProblem::requiredFieldMissing('email');
        }

        try {
            $this->client->subscribe(new EmailAddress($body['email']), $mailingListId);
        }
        catch (InvalidArgumentException $e) {
            throw ApiProblem::failedToSubscribeToNewsletter($e->getMessage());
        }
        catch (MailinglistSubscriptionFailed $e) {
            $this->logger->error('Failed to subscribe to newsletter: ' . $e->getMessage());
            throw ApiProblem::failedToSubscribeToNewsletter($e->getMessage());
        }

        return new Response(StatusCodeInterface::STATUS_NO_CONTENT);
    }
}
