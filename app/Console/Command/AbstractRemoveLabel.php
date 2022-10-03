<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine\DBALReadRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\InMemoryExcludedLabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\StringLiteral;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;

abstract class AbstractRemoveLabel extends Command
{
    private ReadRepositoryInterface $readRepository;

    protected CommandBus $commandBus;

    public function __construct(Connection $connection, CommandBus $commandBus)
    {
        $this->readRepository = new DBALReadRepository(
            $connection,
            new StringLiteral('labels_json'),
            new StringLiteral('label_roles'),
            new StringLiteral('user_roles'),
            new InMemoryExcludedLabelsRepository([])
        );
        $this->commandBus = $commandBus;
        parent::__construct();
    }

    protected function getLabel(string $labelId): ?string
    {
        $uuid = new UUID($labelId);
        $entity = $this->readRepository->getByUuid($uuid);
        return isset($entity) ? $entity->getName()->toNative() : null;
    }
}
