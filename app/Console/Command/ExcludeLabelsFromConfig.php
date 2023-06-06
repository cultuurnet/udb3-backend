<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Label\Commands\ExcludeLabel as ExcludeLabelCommand;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ExcludeLabelsFromConfig extends AbstractCommand
{
    public function configure(): void
    {
        $this->setName('label:bulk-exclude');
        $this->setDescription('Excludes all labels in the config.excluded_labels file');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!file_exists(__DIR__ . '/../../../config.excluded_labels.php')) {
            $output->writeln('Warning: file config.excluded_labels.php is missing.');
            return 0;
        }

        $labelIds = require __DIR__ . '/../../../config.excluded_labels.php';

        if (!$this->askConfirmation($input, $output, count($labelIds))) {
            return 0;
        }

        foreach ($labelIds as $labelId) {
            $this->commandBus->dispatch(
                new ExcludeLabelCommand(new UUID($labelId))
            );
        }

        return 0;
    }

    private function askConfirmation(
        InputInterface $input,
        OutputInterface $output,
        int $count
    ): bool {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This will exclude {$count} labels from the config file, continue? [y/N] ",
                    false
                )
            );
    }
}
