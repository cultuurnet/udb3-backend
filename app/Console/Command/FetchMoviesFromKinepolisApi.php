<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Kinepolis\KinepolisService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class FetchMoviesFromKinepolisApi extends Command
{
    private KinepolisService $service;

    public function __construct(KinepolisService $service)
    {
        parent::__construct();
        $this->service = $service;
    }
    public function configure(): void
    {
        $this->setName('movies:fetch');
        $this->setDescription('Fetches movies from the Kinepolis API');
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Skip confirmation.'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->askConfirmation($input, $output)) {
            $this->service->import();
        }
        return 0;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion('Fetch & import movies from the Kinepolis API? [y/N] ', false)
            );
    }
}
