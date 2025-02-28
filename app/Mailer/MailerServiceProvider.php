<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsReplayed;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Mailer\Handler\Helper\OwnershipMailParamExtractor;
use CultuurNet\UDB3\Mailer\Handler\SendMailsForOwnershipEventHandler;
use CultuurNet\UDB3\Mailer\Handler\SendOwnershipMailCommandHandler;
use CultuurNet\UDB3\Organizer\OrganizerServiceProvider;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
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
            SendOwnershipMailCommandHandler::class,
            SendMailsForOwnershipEventHandler::class,
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
                    new Address(
                        $config['mail']['sender']['address'],
                        $config['mail']['sender']['name']
                    ),
                    LoggerFactory::create($this->container, LoggerName::forWeb()),
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
            SendOwnershipMailCommandHandler::class,
            function (): SendOwnershipMailCommandHandler {
                return new SendOwnershipMailCommandHandler(
                    $this->container->get(Mailer::class),
                    $this->container->get(MailsSentRepository::class),
                    $this->container->get(UserIdentityResolver::class),
                    new TwigEnvironment(
                        new FilesystemLoader(__DIR__ . '/../../src/Mailer/templates'),
                    ),
                    $this->container->get(OwnershipSearchRepository::class),
                    $this->container->get(OwnershipMailParamExtractor::class),
                    LoggerFactory::create($this->container, LoggerName::forResqueWorker('mails')),
                );
            }
        );

        $container->addShared(
            OwnershipMailParamExtractor::class,
            function (): OwnershipMailParamExtractor {
                return new OwnershipMailParamExtractor(
                    $this->container->get('organizer_jsonld_repository'),
                    $this->container->get(OrganizerServiceProvider::ORGANIZER_FRONTEND_IRI_GENERATOR),
                );
            }
        );


        $container->addShared(
            SendMailsForOwnershipEventHandler::class,
            function (): SendMailsForOwnershipEventHandler {
                return new SendMailsForOwnershipEventHandler(
                    $this->container->get('mails_command_bus'),
                    new DomainMessageIsReplayed(),
                );
            }
        );
    }
}
