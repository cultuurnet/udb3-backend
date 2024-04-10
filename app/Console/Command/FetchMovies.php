<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Kinepolis\KinepolisService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FetchMovies extends Command
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
        $this->setDescription('Fetches movies from an external API');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->service->start();
        return 0;
    }
}
