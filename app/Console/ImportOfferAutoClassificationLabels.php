<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use Doctrine\DBAL\Connection;
use Exception;
use Knp\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ImportOfferAutoClassificationLabels extends Command
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(Connection $connection, CommandBus $commandBus)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->commandBus = $commandBus;
    }

    protected function configure(): void
    {
        $this
            ->setName('offer:import-auto-classification-labels')
            ->setDescription('Import auto-classification labels on events and places.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Skip confirmation.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = (int) $this->connection->createQueryBuilder()
            ->select('count(*)')
            ->from('labels_import')
            ->execute()
            ->fetchColumn();

        if ($count === 0) {
            $output->writeln('No rows found in labels_import table');
            return 0;
        }

        if (!$this->askConfirmation($input, $output, $count)) {
            return 0;
        }

        $progress = new ProgressBar($output, $count);

        $rows = $this->connection->createQueryBuilder()
            ->select('offer_id, label')
            ->from('labels_import')
            ->execute();

        $errors = [];

        while ($row = $rows->fetch()) {
            $offerId = (string) $row['offer_id'];
            $label = (string) $row['label'];

            try {
                $this->commandBus->dispatch(new AddLabel($offerId, new Label($label)));
            } catch (Exception $e) {
                $errors[] = [
                    'offer_id' => $offerId,
                    'label' => $label,
                    'exception_type' => get_class($e),
                    'exception_message' => $e->getMessage(),
                ];
            }

            $progress->advance();
        }

        $progress->finish();

        // Write an empty line, otherwise the next line gets written on the same line as the progress bar.
        $output->writeln('');

        $numberOfErrors = count($errors);
        $numberSuccess = $count - $numberOfErrors;

        if ($numberSuccess > 0) {
            $output->writeln('Successfully added ' . $numberSuccess . ' labels to offers.');
        }

        if ($numberOfErrors === 0) {
            return 0;
        }

        $output->writeln('Failed to add ' . $numberOfErrors . ' labels to offers.');

        if ($this->askDebugInfo($input, $output, $numberOfErrors)) {
            foreach ($errors as $error) {
                $output->writeln('');
                $output->writeln('Offer id: ' . $error['offer_id']);
                $output->writeln('Label: ' . $error['label']);
                $output->writeln('Exception type: ' . $error['exception_type']);
                $output->writeln('Exception message: ' . $error['exception_message']);
            }
        }

        return 1;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion('Import ' . $count . ' labels? [y/N] ', false)
            );
    }

    private function askDebugInfo(InputInterface $input, OutputInterface $output, int $count): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion('Show info of ' . $count . ' errors? [y/N] ', false)
            );
    }
}
