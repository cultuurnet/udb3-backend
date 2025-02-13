<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsReplayed;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Mailer\Ownership\SendMailsForOwnership;
use CultuurNet\UDB3\Organizer\OrganizerServiceProvider;
use CultuurNet\UDB3\User\Keycloak\KeycloakUserIdentityResolver;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

class MailerServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            Mailer::class,
            SendMailsForOwnership::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            Mailer::class,
            function () use ($container): Mailer {
                $config = $this->container->get('config');

                return new SmtpMailer(
                    new TwigEnvironment(
                        new FilesystemLoader(__DIR__ . '/templates'),
                    ),
                    new SymfonyMailer(Transport::fromDsn($config['mail']['smtp'])),
                    LoggerFactory::create($container, LoggerName::forWeb()),
                    new Address(
                        $config['mail']['sender']['address'],
                        $config['mail']['sender']['name']
                    ),
                    $this->container->get('config')['mail']['whitelisted_domains'] ?? [],
                );
            }
        );

        $container->addShared(
            SendMailsForOwnership::class,
            function () use ($container): SendMailsForOwnership {
                return new SendMailsForOwnership(
                    new DomainMessageIsReplayed(),
                    $this->container->get(Mailer::class),
                    $this->container->get('organizer_jsonld_repository'),
                    $this->container->get(KeycloakUserIdentityResolver::class),
                    $this->container->get(OrganizerServiceProvider::ORGANIZER_FRONTEND_IRI_GENERATOR),
                    new DBALMailsSentRepository($container->get('dbal_connection')),
                    LoggerFactory::create($container, LoggerName::forWeb()),
                    $this->container->get('config')['mail']['send_organiser_mails'] ?? false,
                );
            }
        );
    }
}
