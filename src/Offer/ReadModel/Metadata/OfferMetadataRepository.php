<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use Doctrine\DBAL\Connection;
use ValueObjects\StringLiteral\StringLiteral;

class OfferMetadataRepository extends AbstractDBALRepository
{
    public const TABLE = 'offer_metadata';

    public function __construct(Connection $connection)
    {
        parent::__construct($connection, new StringLiteral(self::TABLE));
    }

    public function get(string $offerId)
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

    public function save(OfferMetadata $offerMetadata): void
    {
        $this->getConnection()->insert(
            self::TABLE,
            [
                'id' => $offerMetadata->getOfferId(),
                'created_by_api_consumer' => $offerMetadata->getCreatedByApiConsumer(),
            ]
        );
    }
}
