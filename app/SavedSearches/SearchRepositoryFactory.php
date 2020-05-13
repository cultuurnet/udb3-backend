<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\SavedSearches\UDB3SavedSearchRepository;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use ValueObjects\StringLiteral\StringLiteral;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use Doctrine\DBAL\Connection;

class SearchRepositoryFactory
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var UuidGeneratorInterface
     */
    private $uuidGenerator;

    /**
     * @var StringLiteral
     */
    private $userId;

    public function __construct(
        Connection $connection,
        UuidGeneratorInterface $uuidGenerator,
        StringLiteral $userId
    ) {

        $this->connection = $connection;
        $this->uuidGenerator = $uuidGenerator;
        $this->userId = $userId;
    }

    public function createForVersion(string $version): UDB3SavedSearchRepository
    {
        if (!in_array($version, [SapiVersion::V2, SapiVersion::V3])) {
            throw new \InvalidArgumentException('Invalid version value: ' . $version);
        }

        if ($version === SapiVersion::V3) {
            $version = new StringLiteral('saved_searches_sapi3');
        } else {
            $version = new StringLiteral('saved_searches_sapi2');
        }

        return new UDB3SavedSearchRepository(
            $this->connection,
            $version,
            $this->uuidGenerator,
            $this->userId
        );
    }
}
