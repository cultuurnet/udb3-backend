<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\RDF\CacheGraphRepository;
use CultuurNet\UDB3\RDF\GraphStoreRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CopyToFuseki extends Command
{
    private CacheGraphRepository $cacheGraphRepository;
    private GraphStoreRepository $graphStoreRepository;
    private IriGeneratorInterface $iriGenerator;

    public function __construct(
        string $name,
        CacheGraphRepository $cacheGraphRepository,
        GraphStoreRepository $graphStoreRepository,
        IriGeneratorInterface $iriGenerator
    ) {
        $this->cacheGraphRepository = $cacheGraphRepository;
        $this->graphStoreRepository = $graphStoreRepository;
        $this->iriGenerator = $iriGenerator;

        parent::__construct($name);
    }

    public function configure(): void
    {
        $this->setDescription('Copy given id from cache to graph store.');
        $this->addArgument('id', InputArgument::REQUIRED, 'id to copy.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');

        $iri = $this->iriGenerator->iri($id);

        $graph = $this->cacheGraphRepository->get($iri);
        $this->graphStoreRepository->save($iri, $graph);

        return 0;
    }
}
