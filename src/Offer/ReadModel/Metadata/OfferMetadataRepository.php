<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use Doctrine\DBAL\Connection;

class OfferMetadataRepository extends AbstractDBALRepository
{
    public const TABLE = 'offer_metadata';

    public function __construct(Connection $connection)
    {
        parent::__construct($connection, self::TABLE);
    }

    public function get(string $offerId): OfferMetadata
    {
        $result = $this->getConnection()
            ->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE)
            ->where('id = :offerId')
            ->setParameter('offerId', $offerId)
            ->execute()
            ->fetch();

        if (!$result) {
            throw new EntityNotFoundException('Could not find offer metadata for offer with id ' . $offerId);
        }

        return new OfferMetadata(
            $offerId,
            $result['created_by_api_consumer']
        );
    }

    private function exists(string $offerId): bool
    {
        try {
            $this->get($offerId);
            return true;
        } catch (EntityNotFoundException $e) {
            return false;
        }
    }

    public function save(OfferMetadata $offerMetadata): void
    {
        if (!$this->exists($offerMetadata->getOfferId())) {
            $this->insert($offerMetadata);
        } else {
            $this->update($offerMetadata);
        }
    }

    private function insert(OfferMetadata $offerMetadata): void
    {
        $this->getConnection()->insert(
            self::TABLE,
            [
                'id' => $offerMetadata->getOfferId(),
                'created_by_api_consumer' => $offerMetadata->getCreatedByApiConsumer(),
            ]
        );
    }

    private function update(OfferMetadata $offerMetadata): void
    {
        $this->getConnection()->update(
            self::TABLE,
            [
                'created_by_api_consumer' => $offerMetadata->getCreatedByApiConsumer(),
            ],
            [
                'id' => $offerMetadata->getOfferId(),
            ]
        );
    }
}
