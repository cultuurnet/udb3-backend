<?php

namespace CultuurNet\UDB3\Label\Services;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class ReadService implements ReadServiceInterface
{
    /**
     * @var ReadRepositoryInterface
     */
    private $readRepository;

    /**
     * ReadService constructor.
     * @param ReadRepositoryInterface $readRepository
     */
    public function __construct(ReadRepositoryInterface $readRepository)
    {
        $this->readRepository = $readRepository;
    }

    /**
     * @inheritdoc
     */
    public function getByUuid(UUID $uuid)
    {
        return $this->readRepository->getByUuid($uuid);
    }

    /**
     * @inheritdoc
     */
    public function getByName(StringLiteral $identifier)
    {
        return $this->readRepository->getByName($identifier);
    }

    /**
     * @inheritdoc
     */
    public function search(Query $query)
    {
        return $this->readRepository->search($query);
    }

    /**
     * @inheritdoc
     */
    public function searchTotalLabels(Query $query)
    {
        return $this->readRepository->searchTotalLabels($query);
    }
}
