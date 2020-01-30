<?php

namespace CultuurNet\UDB3\UiTID;

use CultuurNet\UDB3\Cdb\CreatedByToUserIdResolverInterface;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolverInterface;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\EmailAddress;

class CdbXmlCreatedByToUserIdResolver implements LoggerAwareInterface, CreatedByToUserIdResolverInterface
{
    use LoggerAwareTrait;

    /**
     * @var UserIdentityResolverInterface
     */
    private $users;

    public function __construct(UserIdentityResolverInterface $users)
    {
        $this->users = $users;
        $this->logger = new NullLogger();
    }

    /**
     * @inheritdoc
     */
    public function resolveCreatedByToUserId(StringLiteral $createdByIdentifier): ?StringLiteral
    {
        try {
            // If the createdby is a UUID, return it immediately.
            UUID::fromNative($createdByIdentifier->toNative());
            return $createdByIdentifier;
        } catch (InvalidNativeArgumentException $exception) {
            $this->logger->info(
                'The provided createdByIdentifier ' . $createdByIdentifier->toNative() . ' is not a UUID.',
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
                $email = new EmailAddress($createdByIdentifier->toNative());
                $user = $this->users->getUserByEmail($email);
            } catch (InvalidNativeArgumentException $e) {
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
