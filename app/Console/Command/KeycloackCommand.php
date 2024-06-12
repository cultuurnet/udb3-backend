<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\User\Keycloack\KeycloackUserIdentityResolver;
use CultuurNet\UDB3\User\UserIdentityDetails;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class KeycloackCommand extends Command
{
    private const OPTION_EMAIL = 'email';
    private const OPTION_ID = 'id';

    private KeycloackUserIdentityResolver $keycloackUserIdentityResolver;

    public function __construct(KeycloackUserIdentityResolver $keycloackUserIdentityResolver)
    {
        parent::__construct();
        $this->keycloackUserIdentityResolver = $keycloackUserIdentityResolver;
    }

    public function configure(): void
    {
        $this->setName('keycloack:find-user')
            ->setDescription('Find a user inside Keycloak either on email or on id')
            ->addOption(self::OPTION_EMAIL, null, InputOption::VALUE_OPTIONAL, 'Email address of the user to find')
            ->addOption(self::OPTION_ID, null, InputOption::VALUE_OPTIONAL, 'ID of the user to find');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Searching Keycloack user...');

        if ($input->getOption(self::OPTION_ID) && $input->getOption(self::OPTION_EMAIL)) {
            $output->writeln('You can only search for a user by id or by email, not both.');
            return self::FAILURE;
        }

        if ($input->getOption(self::OPTION_ID) === null && $input->getOption(self::OPTION_EMAIL) === null) {
            $output->writeln('You need to provide either an id or an email to search for a user.');
            return self::FAILURE;
        }

        $userIdentityDetails = $this->getUserIdentityDetails($input);

        if ($userIdentityDetails === null) {
            $output->writeln('No user found.');
            return self::FAILURE;
        }

        $output->writeln(
            'User found with id: ' . $userIdentityDetails->getUserId() .
            ' and email: ' . $userIdentityDetails->getEmailAddress() .
            ' and username: ' . $userIdentityDetails->getUsername()
        );
        return self::SUCCESS;
    }

    private function getUserIdentityDetails(InputInterface $input): ?UserIdentityDetails
    {
        if ($input->getOption(self::OPTION_ID)) {
            return $this->keycloackUserIdentityResolver->getUserById(
                $input->getOption(self::OPTION_ID)
            );
        }

        if ($input->getOption(self::OPTION_EMAIL)) {
            return $this->keycloackUserIdentityResolver->getUserByEmail(
                new EmailAddress($input->getOption(self::OPTION_EMAIL))
            );
        }

        return null;
    }
}
