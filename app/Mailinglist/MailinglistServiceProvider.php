<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailinglist;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Mailinglist\Client\MailingJetClient;
use CultuurNet\UDB3\Mailinglist\Client\MailinglistClient;

final class MailinglistServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            MailinglistClient::class,
            SubscribeUserToMailinglistRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            MailinglistClient::class,
            function () use ($container) {
                return new MailingJetClient(
                    $container->get('config')['mailjet']['apiKey'] ?? '',
                    $container->get('config')['mailjet']['apiSecret'] ?? ''
                );
            }
        );

        $container->addShared(
            SubscribeUserToMailinglistRequestHandler::class,
            function () use ($container) {
                return new SubscribeUserToMailinglistRequestHandler(
                    $container->get(MailinglistClient::class),
                    LoggerFactory::create($container, LoggerName::forWeb())
                );
            }
        );
    }
}
