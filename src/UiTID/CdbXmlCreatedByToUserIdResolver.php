<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTID;

use CultuurNet\UDB3\Cdb\CreatedByToUserIdResolverInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidEmailAddress;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class CdbXmlCreatedByToUserIdResolver implements LoggerAwareInterface, CreatedByToUserIdResolverInterface
{
    use LoggerAwareTrait;

    private UserIdentityResolver $users;

    public function __construct(UserIdentityResolver $users)
    {
        $this->users = $users;
        $this->logger = new NullLogger();
    }

    public function resolveCreatedByToUserId(string $createdByIdentifier): ?string
    {
        try {
            // If the createdby is a UUID, return it immediately.
            new UUID($createdByIdentifier);
            return $createdByIdentifier;
        } catch (InvalidArgumentException $exception) {
            $this->logger->info(
                'The provided createdByIdentifier ' . $createdByIdentifier . ' is not a UUID.',
                [
                    'exception' => $exception,
                ]
            );
        }

        try {
            // If the createdby is not a UUID, it might still be an Auth0 or social id.
            $user = $this->users->getUserById($createdByIdentifier);
            if ($user instanceof UserIdentityDetails) {
                return $user->getUserId();
            }

            // If no user was found with the createdby as id, check if it's an email and look up the user that way.
            // Otherwise look it up as a username.
            try {
                $email = new EmailAddress($createdByIdentifier);
                $user = $this->users->getUserByEmail($email);
            } catch (InvalidEmailAddress $e) {
                $user = $this->users->getUserByNick($createdByIdentifier);
            }
            if ($user instanceof UserIdentityDetails) {
                return $user->getUserId();
            }
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'An unexpected error occurred while resolving user with identifier %s',
                    $createdByIdentifier
                ),
                [
                    'exception' => $e,
                ]
            );
        }

        $this->logger->warning(
            'Unable to find user with identifier ' . $createdByIdentifier
        );

        return null;
    }
}
