<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ExecuteCommandFromCsv extends Command
{
    private const FILE = 'file';
    private const COMMAND = 'script';
    protected static $defaultName = 'execute-command-from-csv';

    protected function configure(): void
    {
        $this
            ->setDescription('Execute a CLI command for each row in a CSV file')
            ->addArgument(self::FILE, InputArgument::REQUIRED, 'The path to the CSV file')
            ->addArgument(self::COMMAND, InputArgument::REQUIRED, 'The CLI command to execute. You can use %1, %2, ... as a placeholder for the arguments/options in the command.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument(self::FILE);
        $command = 'bin/udb3.php ' . $input->getArgument(self::COMMAND);

        if (!file_exists($file) || !is_readable($file)) {
            $output->writeln(sprintf("<error>Could not find file '%s'</error>", $file));
            return 0;
        }

        $handle = fopen($file, 'rb');

        $progressBar = new ProgressBar($output, count(file($file)));
        $progressBar->start();

        $firstRow = true;
        while (($arguments = fgetcsv($handle)) !== false) {
            if ($firstRow) {
                $firstRow = false;
                continue;
            }

            $commandWithParam = $this->getCommandWithArguments($command, $arguments);

            $process = Process::fromShellCommandline($commandWithParam);
            $process->run();

            if ($process->isSuccessful()) {
                $output->writeln(sprintf('<info>Command executed successfully: %s</info>', $commandWithParam));
            } else {
                $output->writeln(sprintf('<error>Failed to execute command: %s</error>', $commandWithParam));
                $output->writeln(sprintf('<error>Error: %s</error>', $process->getErrorOutput()));
            }

            $progressBar->advance();
        }

        fclose($handle);
        $progressBar->finish();

        return 1;
    }

    private function getCommandWithArguments(string $command, array $arguments): string
    {
        $commandWithParam = $command;
        foreach ($arguments as $i => $argument) {
            $commandWithParam = str_replace('%' . $i, escapeshellarg($argument), $commandWithParam);
        }

        return $commandWithParam;
    }
}
