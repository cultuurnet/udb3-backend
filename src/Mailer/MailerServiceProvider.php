<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsReplayed;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Mailer\Ownership\SendMailsForOwnership;
use CultuurNet\UDB3\Mailer\Queue\MailerCommandBus;
use CultuurNet\UDB3\Organizer\OrganizerServiceProvider;
use CultuurNet\UDB3\User\Keycloak\KeycloakUserIdentityResolver;
use CultuurNet\UDB3\User\UserIdentityResolver;
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
            MailSentCommandHandler::class,
            SendMailsForOwnership::class,
            MailsSentRepository::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            Mailer::class,
            function (): Mailer {
                $config = $this->container->get('config');
                return new SmtpMailer(
                    new SymfonyMailer(Transport::fromDsn($config['mail']['smtp'])),
                    LoggerFactory::create($this->container, LoggerName::forWeb()),
                    new Address(
                        $config['mail']['sender']['address'],
                        $config['mail']['sender']['name']
                    ),
                );
            }
        );

        $container->addShared(
            MailsSentRepository::class,
            function () use ($container): MailsSentRepository {
                return new DBALMailsSentRepository($container->get('dbal_connection'));
            }
        );

        $container->addShared(
            MailSentCommandHandler::class,
            function (): MailSentCommandHandler {
                return new MailSentCommandHandler(
                    $this->container->get(Mailer::class),
                    $this->container->get(MailsSentRepository::class),
                    LoggerFactory::create($this->container, LoggerName::forResqueWorker(MailerCommandBus::getQueueName())),
                );
            }
        );

        $container->addShared(
            SendMailsForOwnership::class,
            function (): SendMailsForOwnership {
                return new SendMailsForOwnership(
                    $this->container->get(MailerCommandBus::class),
                    new DomainMessageIsReplayed(),
                    $this->container->get('organizer_jsonld_repository'),
                    $this->container->get(UserIdentityResolver::class),
                    $this->container->get(OrganizerServiceProvider::ORGANIZER_FRONTEND_IRI_GENERATOR),
                    new TwigEnvironment(
                        new FilesystemLoader(__DIR__ . '/templates'),
                    ),
                    LoggerFactory::create($this->container, LoggerName::forWeb()),
                    $this->container->get('config')['mail']['send_organiser_mails'] ?? false,
                );
            }
        );
    }
}
