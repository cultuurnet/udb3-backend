<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Ownership\Commands\RequestOwnership;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class RequestOwnershipDenormalizer implements DenormalizerInterface
{
    private UuidFactoryInterface $uuidFactory;


    public function __construct(UuidFactoryInterface $uuidFactory)
    {
        $this->uuidFactory = $uuidFactory;
    }

    public function denormalize($data, $class, $format = null, array $context = []): RequestOwnership
    {
        return new RequestOwnership(
            new UUID($this->uuidFactory->uuid4()->toString()),
            new UUID($data['itemId']),
            new ItemType($data['itemType']),
            new UserId($data['ownerId'])
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === RequestOwnership::class;
    }
}
