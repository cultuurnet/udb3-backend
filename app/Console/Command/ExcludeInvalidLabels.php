<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Label\Commands\ExcludeLabel as ExcludeLabelCommand;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ExcludeInvalidLabels extends AbstractCommand
{
    private const MAX_RESULTS = 500;
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

        $firstResult = 0;
        do {
            $labels = $this->getLabelsFromFirstResult($firstResult);

            foreach ($labels as $label) {
                $labelId = new UUID($label['uuid_col']);
                $labelName = $label['name'];

                if (!preg_match(LabelName::REGEX_SUGGESTIONS, $labelName)) {
                    $this->commandBus->dispatch(
                        new ExcludeLabelCommand($labelId)
                    );
                }
                $progressBar->advance();
            }

            $firstResult += self::MAX_RESULTS;
        } while (count($labels) === self::MAX_RESULTS);

        $progressBar->finish();
        $output->writeln('');

        return 0;
    }

    private function getAllLabelsCount(): int
    {
        return count(
            $this->connection->createQueryBuilder()->select('uuid_col')
                ->from('labels_json')
                ->where('excluded = :excluded')
                ->setParameter(':excluded', 0)
                ->execute()
                ->fetchAllAssociative()
        );
    }

    private function getLabelsFromFirstResult(int $firstResult): array
    {
        return $this->connection->createQueryBuilder()
            ->select('uuid_col, name')
            ->from('labels_json')
            ->where('excluded = :excluded')
            ->setParameter(':excluded', 0)
            ->setFirstResult($firstResult)
            ->setMaxResults(self::MAX_RESULTS)
            ->execute()
            ->fetchAllAssociative();
    }
}
