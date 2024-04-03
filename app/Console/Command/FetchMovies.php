<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FetchMovies extends AbstractCommand
{
    public function configure(): void
    {
        $this->setName('movies:fetch');
        $this->setDescription('Fetches movies from an external API');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // TODO
        $output->writeln('TODO');
        return 0;
    }
}