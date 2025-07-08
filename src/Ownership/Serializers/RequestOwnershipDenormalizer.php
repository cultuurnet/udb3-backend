<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Serializers;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Commands\RequestOwnership;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityResolver;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\UuidFactory;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class RequestOwnershipDenormalizer implements DenormalizerInterface
{
    private UuidFactory $uuidFactory;
    private CurrentUser $currentUser;
    private UserIdentityResolver $identityResolver;

    public function __construct(UuidFactory $uuidFactory, UserIdentityResolver $identityResolver, CurrentUser $currentUser)
    {
        $this->uuidFactory = $uuidFactory;
        $this->currentUser = $currentUser;
        $this->identityResolver = $identityResolver;
    }

    public function denormalize($data, $class, $format = null, array $context = []): RequestOwnership
    {
        if ($userId = $data['ownerId'] ?? null) {
            $userDetails = $this->identityResolver->getUserById($userId);
            if ($userDetails === null) {
                throw ApiProblem::bodyInvalidDataWithDetail('No user with id ' . $userId . ' was found in our system.');
            }
        }
        if ($email = $data['ownerEmail'] ?? null) {
            $user = $this->identityResolver->getUserByEmail(new EmailAddress($email));
            if (!$user) {
                throw ApiProblem::bodyInvalidDataWithDetail('No user with email ' . $email . ' was found in our system.');
            }

            $data['ownerId'] = $user->getUserId();
        }

        if (!isset($data['ownerId']) && !isset($data['ownerEmail'])) {
            $data['ownerId'] = $this->currentUser->getId();
        }

        return new RequestOwnership(
            new Uuid($this->uuidFactory->uuid4()->toString()),
            new Uuid($data['itemId']),
            new ItemType($data['itemType']),
            new UserId($data['ownerId']),
            new UserId($this->currentUser->getId())
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === RequestOwnership::class;
    }
}
