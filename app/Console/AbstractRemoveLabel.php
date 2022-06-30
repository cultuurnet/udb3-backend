<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;

abstract class AbstractRemoveLabel extends Command
{
    private Connection $connection;

    protected CommandBus $commandBus;

    public function __construct(Connection $connection, CommandBus $commandBus)
    {
        $this->connection = $connection;
        $this->commandBus = $commandBus;
        parent::__construct();
    }

    /*
     * @return string|false
     */
    protected function getLabel(string $labelId)
    {
        return $this->connection->createQueryBuilder()
            ->select('unique_col')
            ->from('labels_unique')
            ->where('uuid_col = "' . $labelId . '"')
            ->execute()
            ->fetchColumn();
    }
}
