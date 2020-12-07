<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractGeocodeCommand extends AbstractCommand
{
    /**
     * @var ResultsGeneratorInterface
     */
    private $searchResultsGenerator;

    /**
     * @var DocumentRepository
     */
    private $documentRepository;

    public function __construct(
        CommandBusInterface $commandBus,
        ResultsGeneratorInterface $searchResultsGenerator,
        DocumentRepository $documentRepository
    ) {
        parent::__construct($commandBus);
        $this->searchResultsGenerator = $searchResultsGenerator;
        $this->documentRepository = $documentRepository;
    }

    protected function getDocument(string $id): ?JsonDocument
    {
        try {
            return $this->documentRepository->fetch($id);
        } catch (DocumentDoesNotExist $e) {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cdbids = array_values($input->getOption('cdbid'));

        if ($input->getOption('all')) {
            $cdbids = $this->getAllCdbIds();
        } elseif (empty($cdbids)) {
            $cdbids = $this->getOutdatedCdbIds();
        }

        $count = count($cdbids);

        if ($count == 0) {
            $output->writeln("Could not find any items with missing or outdated coordinates.");
            return 0;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "This action will queue {$count} items for geocoding, continue? [y/N] ",
            true
        );

        if (!$helper->ask($input, $output, $question)) {
            return 0;
        }

        foreach ($cdbids as $cdbid) {
            $this->dispatchGeocodingCommand($cdbid, $output);
        }

        return 0;
    }

    /**
     * @param string $itemId
     * @param OutputInterface $output
     */
    abstract protected function dispatchGeocodingCommand($itemId, OutputInterface $output);
}
