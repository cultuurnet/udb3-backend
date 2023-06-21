<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine\DBALReadRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
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
            'labels_json',
            'label_roles',
            'user_roles'
        );
        $this->commandBus = $commandBus;
        parent::__construct();
    }

    protected function getLabel(string $labelId): ?string
    {
        $uuid = new UUID($labelId);
        $entity = $this->readRepository->getByUuid($uuid);
        return isset($entity) ? $entity->getName() : null;
    }
}
