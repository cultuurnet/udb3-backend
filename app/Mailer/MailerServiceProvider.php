<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\Broadway\Domain\DomainMessageHasMailsDisabled;
use CultuurNet\UDB3\Broadway\Domain\DomainMessageIsReplayed;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Mailer\Handler\Helper\OwnershipMailParamExtractor;
use CultuurNet\UDB3\Mailer\Handler\SendMailsForOwnershipEventHandler;
use CultuurNet\UDB3\Mailer\Handler\SendOwnershipMailCommandHandler;
use CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy\CombinedRecipientStrategy;
use CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy\SendToCreatorOfOrganisation;
use CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy\SendToOwnerOfOwnership;
use CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy\SendToOwnersOfOrganisation;
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

        $logger = LoggerFactory::create($this->container, LoggerName::forResqueWorker('mails'));

        $container->addShared(
            SendOwnershipMailCommandHandler::class,
            function () use ($logger): SendOwnershipMailCommandHandler {
                return new SendOwnershipMailCommandHandler(
                    $this->container->get(Mailer::class),
                    new TwigEnvironment(
                        new FilesystemLoader(__DIR__ . '/../../src/Mailer/templates'),
                    ),
                    $this->container->get(OwnershipSearchRepository::class),
                    $this->container->get(OwnershipMailParamExtractor::class),
                    new CombinedRecipientStrategy(
                        new SendToCreatorOfOrganisation(
                            $this->container->get(UserIdentityResolver::class),
                            $this->container->get('organizer_jsonld_repository'),
                        ),
                        new SendToOwnersOfOrganisation(
                            $this->container->get(UserIdentityResolver::class),
                            $this->container->get(OwnershipSearchRepository::class)
                        ),
                    ),
                    new SendToOwnerOfOwnership($this->container->get(UserIdentityResolver::class)),
                    $logger,
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
                    new DomainMessageHasMailsDisabled()
                );
            }
        );
    }
}
