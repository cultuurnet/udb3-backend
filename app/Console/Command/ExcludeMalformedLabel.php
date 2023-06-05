<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Label\Commands\ExcludeLabel as ExcludeLabelCommand;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ExcludeMalformedLabel extends AbstractCommand
{
    private Connection $connection;

    public function configure(): void
    {
        $this->setName('label:exclude-malformed');
        $this->setDescription('Excludes 1000 malformed labels');
    }

    public function __construct(CommandBus $commandBus, Connection $connection)
    {
        parent::__construct($commandBus);
        $this->connection = $connection;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $malformedLabelIds = $queryBuilder->select('uuid')
            ->from('labels_json')
            ->Where('exclude = :excluded')
            ->setParameter(':excluded', 0)
            ->andWhere('name REGEXP \'^[a-zA-Z\d_\-]{2,50}$\'')
            ->setMaxResults(1000)
            ->execute()
            ->fetchAll(FetchMode::COLUMN);

        foreach ($malformedLabelIds as $malformedLabelId) {
            $this->commandBus->dispatch(
                new ExcludeLabelCommand(new UUID($malformedLabelId))
            );
        }

        return 0;
    }
}
