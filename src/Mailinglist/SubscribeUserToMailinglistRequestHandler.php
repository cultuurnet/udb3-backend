<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailinglist;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Mailinglist\Client\MailinglistClient;
use CultuurNet\UDB3\Mailinglist\Client\MailinglistSubscriptionFailed;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class SubscribeUserToMailinglistRequestHandler implements RequestHandlerInterface
{
    private MailinglistClient $client;
    private LoggerInterface $logger;

    public function __construct(MailinglistClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $email = (new RouteParameters($request))->get('email');
        $mailingListId = (new RouteParameters($request))->get('mailingListId');

        try {
            $this->client->subscribe($email, $mailingListId);
        } catch (MailinglistSubscriptionFailed $e) {
            $this->logger->error('Failed to subscribe to newsletter: ' . $e->getMessage());
            return new JsonResponse(['status' => $e->getMessage()], StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'ok']);
    }
}
