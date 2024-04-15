<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailinglist\Client;

use Mailjet\Client;
use Mailjet\Resources;

class MailingJetClient implements MailinglistClient
{
    private Client $client;

    public function __construct(string $apiKey, string $apiSecret)
    {
        $this->client = new Client(
            $apiKey,
            $apiSecret
        );
    }

    public function subscribe(string $email, string $mailingListId): void
    {
        $mailjetResponse = $this->client->post(
            Resources::$ContactslistManagecontact,
            [
                'id' => $mailingListId,
                'body' => [
                    'Email' => $email,
                    'Action' => 'addnoforce',
                ],
            ]
        );

        if (!$mailjetResponse->success()) {
            throw new MailinglistSubscriptionFailed($mailjetResponse->getReasonPhrase());
        }
    }
}
