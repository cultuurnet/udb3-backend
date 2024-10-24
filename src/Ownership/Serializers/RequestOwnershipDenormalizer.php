<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Ownership\Commands\RequestOwnership;
use CultuurNet\UDB3\User\CurrentUser;
use CultuurNet\UDB3\User\UserIdentityResolver;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class RequestOwnershipDenormalizer implements DenormalizerInterface
{
    private UuidFactoryInterface $uuidFactory;
    private CurrentUser $currentUser;
    private UserIdentityResolver $identityResolver;

    public function __construct(UuidFactoryInterface $uuidFactory, UserIdentityResolver $identityResolver, CurrentUser $currentUser)
    {
        $this->uuidFactory = $uuidFactory;
        $this->currentUser = $currentUser;
        $this->identityResolver = $identityResolver;
    }

    public function denormalize($data, $class, $format = null, array $context = []): RequestOwnership
    {
        if ($email = $data['ownerEmail'] ?? null) {
            $user = $this->identityResolver->getUserByEmail($email);
            if ($user) {
                $data['ownerId'] = $user->getUserId();
            }
        }

        return new RequestOwnership(
            new UUID($this->uuidFactory->uuid4()->toString()),
            new UUID($data['itemId']),
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
