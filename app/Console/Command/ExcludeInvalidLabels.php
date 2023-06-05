<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Label\Commands\ExcludeLabel as ExcludeLabelCommand;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Doctrine\DBAL\Connection;
use PDO;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ExcludeInvalidLabels extends AbstractCommand
{
    private const LABEL_REGEX = '/^[a-zA-Z\d_\-]{2,50}$/';

    private const MAX_RESULTS = 1;
    private Connection $connection;

    public function configure(): void
    {
        $this->setName('label:exclude-invalid');
        $this->setDescription('Excludes invalid labels based on a regex');
    }

    public function __construct(CommandBus $commandBus, Connection $connection)
    {
        parent::__construct($commandBus);
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $labelsCount = $this->getAllLabelsCount();

        $helper = $this->getHelper('question');
        $updateQuestion = new ConfirmationQuestion('Check ' . $labelsCount . ' label(s)? [y/N] ', false);
        if (!$helper->ask($input, $output, $updateQuestion)) {
            return 0;
        }

        $progressBar = new ProgressBar($output, $labelsCount);

        $offset = 0;
        do {
            $labels = $this->getAllLabels($offset);

            foreach ($labels as $label) {
                $labelId = new Uuid($label['uuid_col']);
                $labelName = $label['name'];
                $excluded = (bool) $label['excluded'];

                if (!$excluded && !preg_match(self::LABEL_REGEX, $labelName)) {
                    $this->commandBus->dispatch(
                        new ExcludeLabelCommand($labelId)
                    );
                }
                $progressBar->advance();
            }

            $offset += count($labels);
        } while ($offset < $labelsCount);

        $progressBar->finish();
        $output->writeln('');

        return 0;
    }

    private function getAllLabelsCount(): int
    {
        return $this->connection->createQueryBuilder()->select('*')
            ->from('labels_json')
            ->execute()
            ->rowCount();
    }

    private function getAllLabels(int $offset): array
    {
        return $this->connection->createQueryBuilder()
            ->select('uuid_col, name, excluded')
            ->from('labels_json')
            ->setFirstResult($offset)
            ->setMaxResults(self::MAX_RESULTS)
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}
